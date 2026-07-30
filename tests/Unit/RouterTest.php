<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Router;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for route path normalization / resolution.
 */
final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router();
    }

    public function testNormalizeTrimsSlashes(): void
    {
        $method = new ReflectionMethod(Router::class, 'normalize');
        $method->setAccessible(true);

        $this->assertSame('/', $method->invoke($this->router, ''));
        $this->assertSame('/', $method->invoke($this->router, '/'));
        $this->assertSame('/report', $method->invoke($this->router, 'report/'));
        $this->assertSame('/purchase/store', $method->invoke($this->router, '/purchase/store/'));
    }

    public function testResolvePathUsesPathInfo(): void
    {
        $_SERVER['PATH_INFO'] = '/report';
        $_SERVER['SCRIPT_NAME'] = '/purchase-entry-track/public/index.php';
        $_SERVER['REQUEST_URI'] = '/purchase-entry-track/public/index.php/report';

        $method = new ReflectionMethod(Router::class, 'resolvePath');
        $method->setAccessible(true);

        $this->assertSame('/report', $method->invoke($this->router, $_SERVER['REQUEST_URI']));

        unset($_SERVER['PATH_INFO']);
    }

    public function testUnknownRouteReturns404(): void
    {
        ob_start();
        $this->router->dispatch('GET', '/does-not-exist');
        $output = ob_get_clean();

        $this->assertSame(404, http_response_code());
        $this->assertStringContainsString('404', (string) $output);
    }
}
