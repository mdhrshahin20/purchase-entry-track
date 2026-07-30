<?php

namespace App\Foundation;

/**
 * Application kernel — boots config, timezone, and the container.
 */
class Application extends Container
{
    private string $basePath;

    /** @var array<string, array<string, mixed>> */
    private array $config = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->instance(self::class, $this);
        $this->instance(Container::class, $this);
    }

    public function basePath(string $path = ''): string
    {
        return $path === ''
            ? $this->basePath
            : $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }

    public function bootstrap(): self
    {
        $this->loadConfig();
        date_default_timezone_set((string) $this->config('app.timezone', 'UTC'));

        $this->singleton(Database::class, static fn () => Database::getInstance());
        $this->singleton(Router::class, fn () => new Router($this));
        $this->singleton(View::class, fn () => new View($this->basePath('resources/views')));

        return $this;
    }

    private function loadConfig(): void
    {
        foreach (['app', 'database'] as $name) {
            $file = $this->basePath('config/' . $name . '.php');
            $this->config[$name] = is_file($file) ? require $file : [];
        }
    }

    public function config(string $key, mixed $default = null): mixed
    {
        [$file, $item] = array_pad(explode('.', $key, 2), 2, null);
        if ($item === null) {
            return $this->config[$file] ?? $default;
        }

        return $this->config[$file][$item] ?? $default;
    }

    public function router(): Router
    {
        return $this->make(Router::class);
    }

    public function handle(Request $request): Response
    {
        return $this->router()->dispatch($request);
    }
}
