<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session-backed CSRF token generation and validation.
 */
class Csrf
{
    /**
     * Session key used to store the token.
     */
    private const SESSION_KEY = '_csrf_token';

    /**
     * Start the PHP session if it is not already active.
     *
     * @return void
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /**
     * Return the current CSRF token, generating one when missing.
     *
     * @return string Hex-encoded token (64 characters).
     */
    public static function token(): string
    {
        self::startSession();

        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Compare a submitted token with the session token using timing-safe equals.
     *
     * @param string|null $token Token from the client.
     *
     * @return bool True when the token is present and matches.
     */
    public static function validate(?string $token): bool
    {
        self::startSession();

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';
        if (!is_string($sessionToken) || $sessionToken === '' || $token === null || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Read a CSRF token from POST `_token` or the `X-CSRF-TOKEN` header.
     *
     * @return string|null
     */
    public static function tokenFromRequest(): ?string
    {
        if (isset($_POST['_token']) && is_string($_POST['_token'])) {
            return $_POST['_token'];
        }

        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return is_string($header) ? $header : null;
    }
}
