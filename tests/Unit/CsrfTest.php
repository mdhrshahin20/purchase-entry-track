<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for session-backed CSRF tokens.
 */
final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $_SESSION = [];
        $_POST = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    protected function tearDown(): void
    {
        $_POST = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }

        parent::tearDown();
    }

    public function testTokenIsGeneratedAndStableWithinSession(): void
    {
        $first = Csrf::token();
        $second = Csrf::token();

        $this->assertNotSame('', $first);
        $this->assertSame(64, strlen($first));
        $this->assertSame($first, $second);
        $this->assertTrue(Csrf::validate($first));
    }

    public function testInvalidTokenIsRejected(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::validate('deadbeef'));
        $this->assertFalse(Csrf::validate(null));
        $this->assertFalse(Csrf::validate(''));
    }

    public function testTokenFromPostBody(): void
    {
        $token = Csrf::token();
        $_POST['_token'] = $token;

        $this->assertSame($token, Csrf::tokenFromRequest());
        $this->assertTrue(Csrf::validate(Csrf::tokenFromRequest()));
    }

    public function testTokenFromHeader(): void
    {
        $token = Csrf::token();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        $this->assertSame($token, Csrf::tokenFromRequest());
        $this->assertTrue(Csrf::validate(Csrf::tokenFromRequest()));
    }
}
