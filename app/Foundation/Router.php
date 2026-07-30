<?php

namespace App\Foundation;

/**
 * HTTP router with middleware pipeline (Laravel-inspired).
 */
class Router
{
    private Application $app;

    /** @var array<int, array{method:string, path:string, action:callable|array, middleware:list<class-string>}> */
    private array $routes = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /** @param callable|array{0: class-string, 1: string} $action */
    public function get(string $path, callable|array $action, array $middleware = []): self
    {
        return $this->add('GET', $path, $action, $middleware);
    }

    /** @param callable|array{0: class-string, 1: string} $action */
    public function post(string $path, callable|array $action, array $middleware = []): self
    {
        return $this->add('POST', $path, $action, $middleware);
    }

    /** @param callable|array{0: class-string, 1: string} $action */
    private function add(string $method, string $path, callable|array $action, array $middleware): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->normalize($path),
            'action' => $action,
            'middleware' => $middleware,
        ];
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || $route['path'] !== $path) {
                continue;
            }

            $destination = function (Request $req) use ($route): Response {
                return $this->runAction($route['action'], $req);
            };

            $pipeline = array_reduce(
                array_reverse($route['middleware']),
                static function (callable $next, string $middlewareClass): callable {
                    return static function (Request $req) use ($next, $middlewareClass): Response {
                        /** @var \App\Http\Middleware\MiddlewareInterface $middleware */
                        $middleware = new $middlewareClass();
                        return $middleware->handle($req, $next);
                    };
                },
                $destination
            );

            return $pipeline($request);
        }

        return Response::make('404 – Page not found', 404);
    }

    /** @param callable|array{0: class-string, 1: string} $action */
    private function runAction(callable|array $action, Request $request): Response
    {
        if (is_callable($action) && !is_array($action)) {
            $result = $action($request);
            return $result instanceof Response ? $result : Response::make((string) $result);
        }

        [$class, $method] = $action;
        $controller = $this->app->make($class);

        // Inject FormRequest subclasses by type-hint when possible
        $reflection = new \ReflectionMethod($controller, $method);
        $args = [];
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->getName() === 'request' || ($type && $type->getName() === Request::class)) {
                    $args[] = $request;
                }
                continue;
            }

            $typeName = $type->getName();
            if ($typeName === Request::class || is_subclass_of($typeName, Request::class)) {
                if (is_subclass_of($typeName, \App\Http\Requests\FormRequest::class)) {
                    /** @var \App\Http\Requests\FormRequest $formRequest */
                    $formRequest = $typeName::createFrom($request);
                    $formRequest->validateResolved();
                    $args[] = $formRequest;
                } else {
                    $args[] = $request;
                }
                continue;
            }

            $args[] = $this->app->make($typeName);
        }

        if ($args === []) {
            $args = [$request];
        }

        $result = $reflection->invokeArgs($controller, $args);
        return $result instanceof Response ? $result : Response::make((string) $result);
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
