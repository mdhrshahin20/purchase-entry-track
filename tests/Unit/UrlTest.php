<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use App\Core\Url;
use PHPUnit\Framework\TestCase;

/**
 * Default pretty_urls=false → /index.php/report (works without rewrite).
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

    public function testPathInfoUrlsAtDomainRoot(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = '/home/user/public_html/index.php';
        $_SERVER['DOCUMENT_ROOT'] = '/home/user/public_html';
        $_SERVER['REQUEST_URI'] = '/';

        $this->assertFalse(Url::prettyUrls());
        $this->assertSame('/', Url::home());
        $this->assertSame('/index.php/report', Url::path('/report'));
        $this->assertSame('/index.php/store', Url::endpoint('store'));
    }

    public function testPathInfoUrlsInSubdirectory(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/purchase-entry-track/index.php';
        $_SERVER['SCRIPT_FILENAME'] = '/Applications/MAMP/htdocs/purchase-entry-track/index.php';
        $_SERVER['DOCUMENT_ROOT'] = '/Applications/MAMP/htdocs';
        $_SERVER['REQUEST_URI'] = '/purchase-entry-track/';

        $this->assertSame('/purchase-entry-track/index.php/report', Url::path('/report'));
        $this->assertSame('/purchase-entry-track/index.php/store', Url::endpoint('store'));
    }

    public function testWindowsDriveLetterDoesNotLeakIntoLinks(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/D:/xampp/htdocs/purchase-entry-track/index.php';
        $_SERVER['SCRIPT_FILENAME'] = 'D:/xampp/htdocs/purchase-entry-track/index.php';
        $_SERVER['DOCUMENT_ROOT'] = 'D:/xampp/htdocs';
        $_SERVER['REQUEST_URI'] = '/purchase-entry-track/';

        $url = Url::path('/report');
        $this->assertStringNotContainsString('D:', $url);
        $this->assertSame('/purchase-entry-track/index.php/report', $url);
    }

    public function testRejectsDriveLetterPaths(): void
    {
        $this->assertFalse(Url::isSafeUrlPath('/D:/xampp/htdocs/x'));
        $this->assertTrue(Url::isSafeUrlPath('/report'));
    }
}
