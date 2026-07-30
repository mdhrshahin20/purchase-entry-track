<?php

namespace App\Foundation;

/**
 * Lightweight service container (Laravel-inspired).
 */
class Container
{
    /** @var array<string, mixed> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function singleton(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            if (class_exists($abstract)) {
                return new $abstract();
            }
            throw new \RuntimeException("Target [{$abstract}] is not bound.");
        }

        $instance = ($this->bindings[$abstract])($this);
        $this->instances[$abstract] = $instance;

        return $instance;
    }
}
