<?php

namespace App\Foundation;

/**
 * HTTP response helper (Laravel-inspired).
 */
class Response
{
    private string $content;
    private int $status;

    /** @var array<string, string> */
    private array $headers;

    /** @param array<string, string> $headers */
    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function make(string $content, int $status = 200): self
    {
        return new self($content, $status);
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        return new self(
            (string) json_encode($data),
            $status,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    public static function view(string $view, array $data = [], string $layout = 'layouts/main'): self
    {
        /** @var Application $app */
        $app = $GLOBALS['app'] ?? null;
        if (!$app instanceof Application) {
            throw new \RuntimeException('Application is not bootstrapped.');
        }

        /** @var View $renderer */
        $renderer = $app->make(View::class);
        return new self($renderer->render($view, $data, $layout));
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->content;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function status(): int
    {
        return $this->status;
    }
}
