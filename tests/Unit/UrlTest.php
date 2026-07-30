<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use App\Core\Url;
use PHPUnit\Framework\TestCase;

/**
 * URL helpers — clean paths (no index.php) + Windows/XAMPP safety.
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
        $this->assertFalse(Url::isSafeUrlPath('/D:/xampp/htdocs/purchase-entry-track/index.php'));
        $this->assertTrue(Url::isSafeUrlPath('/purchase-entry-track/report'));
    }

    public function testCleanUrlsAtDomainRoot(): void
    {
        putenv('APP_BASE_URL=');
        $_ENV['APP_BASE_URL'] = '';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/';

        $this->assertSame('/', Url::home());
        $this->assertSame('/report', Url::path('/report'));
        $this->assertSame('/store', Url::endpoint('store'));
        $this->assertStringNotContainsString('index.php', Url::path('/report'));
    }

    public function testCleanUrlsInSubdirectory(): void
    {
        putenv('APP_BASE_URL=/purchase-entry-track');
        $_ENV['APP_BASE_URL'] = '/purchase-entry-track';
        $_SERVER['SCRIPT_NAME'] = '/purchase-entry-track/index.php';
        $_SERVER['REQUEST_URI'] = '/purchase-entry-track/';

        $this->assertSame('/purchase-entry-track', Url::home());
        $this->assertSame('/purchase-entry-track/report', Url::path('/report'));
        $this->assertSame('/purchase-entry-track/store', Url::endpoint('store'));
        $this->assertStringNotContainsString('index.php', Url::path('/report'));
    }

    public function testWindowsDriveLeakStillYieldsCleanPath(): void
    {
        putenv('APP_BASE_URL=/purchase-entry-track');
        $_ENV['APP_BASE_URL'] = '/purchase-entry-track';
        $_SERVER['SCRIPT_NAME'] = '/D:/xampp/htdocs/purchase-entry-track/index.php';

        $this->assertSame('/purchase-entry-track/report', Url::path('/report'));
    }

    public function testRequestPathFromPrettyUrl(): void
    {
        unset($_SERVER['PATH_INFO']);
        putenv('APP_BASE_URL=/purchase-entry-track');
        $_ENV['APP_BASE_URL'] = '/purchase-entry-track';
        $_SERVER['SCRIPT_NAME'] = '/purchase-entry-track/index.php';
        $_SERVER['REQUEST_URI'] = '/purchase-entry-track/report';

        $this->assertSame('/report', Url::requestPath());
    }
}
