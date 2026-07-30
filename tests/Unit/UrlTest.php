<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use App\Core\Url;
use PHPUnit\Framework\TestCase;

/**
 * URL helpers — Windows/XAMPP quirks + single front-controller PATH_INFO links.
 */
final class UrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::clear();
        putenv('APP_BASE_URL');
        unset($_ENV['APP_BASE_URL'], $_SERVER['APP_BASE_URL']);
        unset(
            $_SERVER['PATH_INFO'],
            $_SERVER['SCRIPT_NAME'],
            $_SERVER['PHP_SELF'],
            $_SERVER['SCRIPT_FILENAME'],
            $_SERVER['DOCUMENT_ROOT'],
            $_SERVER['REQUEST_URI']
        );
    }

    protected function tearDown(): void
    {
        putenv('APP_BASE_URL');
        unset($_ENV['APP_BASE_URL'], $_SERVER['APP_BASE_URL']);
        Config::clear();
        parent::tearDown();
    }

    public function testRejectsDriveLetterPaths(): void
    {
        $this->assertFalse(Url::isSafeUrlPath('D:/xampp/htdocs/purchase-entry-track/index.php'));
        $this->assertFalse(Url::isSafeUrlPath('/D:/xampp/htdocs/purchase-entry-track/index.php'));
        $this->assertTrue(Url::isSafeUrlPath('/purchase-entry-track/index.php'));
    }

    public function testPathsUseSingleFrontController(): void
    {
        putenv('APP_BASE_URL=');
        $_ENV['APP_BASE_URL'] = '';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/';

        $this->assertSame('/index.php/report', Url::path('/report'));
        $this->assertSame('/index.php/store', Url::endpoint('store'));
        $this->assertSame('/', Url::home());
    }

    public function testSubdirectoryFrontController(): void
    {
        putenv('APP_BASE_URL=/purchase-entry-track');
        $_ENV['APP_BASE_URL'] = '/purchase-entry-track';
        $_SERVER['SCRIPT_NAME'] = '/purchase-entry-track/index.php';
        $_SERVER['REQUEST_URI'] = '/purchase-entry-track/';

        $this->assertSame('/purchase-entry-track', Url::home());
        $this->assertSame('/purchase-entry-track/index.php/report', Url::path('/report'));
        $this->assertSame('/purchase-entry-track/index.php/store', Url::endpoint('store'));
    }

    public function testConfiguredBaseUrlWinsOnWindowsLeak(): void
    {
        putenv('APP_BASE_URL=/purchase-entry-track');
        $_ENV['APP_BASE_URL'] = '/purchase-entry-track';
        $_SERVER['SCRIPT_NAME'] = '/D:/xampp/htdocs/purchase-entry-track/index.php';
        $_SERVER['REQUEST_URI'] = '/D:/xampp/htdocs/purchase-entry-track/';

        $this->assertSame('/purchase-entry-track/index.php/report', Url::path('/report'));
    }

    public function testRequestPathFromPathInfo(): void
    {
        $_SERVER['PATH_INFO'] = '/report';
        $_SERVER['SCRIPT_NAME'] = '/purchase-entry-track/index.php';
        $_SERVER['REQUEST_URI'] = '/purchase-entry-track/index.php/report';

        $this->assertSame('/report', Url::requestPath());
    }
}
