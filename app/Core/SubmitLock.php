<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 24-hour purchase submit lock helpers (cookie value = unlock unix timestamp).
 */
class SubmitLock
{
    /**
     * @param array<string, mixed> $config Application config from config/app.php.
     *
     * @return array{
     *   locked: bool,
     *   available_at: string,
     *   available_at_label: string,
     *   remaining_seconds: int,
     *   remaining_label: string,
     *   message: string
     * }|null
     */
    public static function status(array $config): ?array
    {
        $cookieName = (string) ($config['submit_cookie_name'] ?? 'purchase_submitted');
        if (!isset($_COOKIE[$cookieName]) || $_COOKIE[$cookieName] === '') {
            return null;
        }

        $raw = (string) $_COOKIE[$cookieName];
        $now = time();
        $ttl = max(1, (int) ($config['submit_cookie_ttl'] ?? 86400));
        $availableAt = self::parseUnlockTimestamp($raw);

        // Old cookie value was just "1" (no unlock time). Upgrade it once to a real timestamp.
        if ($availableAt === null) {
            $availableAt = $now + $ttl;
            self::writeCookie($cookieName, $availableAt);
            $_COOKIE[$cookieName] = (string) $availableAt;
        }

        if ($availableAt <= $now) {
            return null;
        }

        return self::buildStatus($availableAt, $now);
    }

    /**
     * Set the HttpOnly lock cookie to the unlock unix timestamp.
     *
     * @param array<string, mixed> $config
     *
     * @return array{
     *   available_at: string,
     *   available_at_label: string,
     *   remaining_seconds: int,
     *   remaining_label: string
     * }
     */
    public static function setCookie(array $config): array
    {
        $ttl = max(1, (int) ($config['submit_cookie_ttl'] ?? 86400));
        $cookieName = (string) ($config['submit_cookie_name'] ?? 'purchase_submitted');
        $availableAt = time() + $ttl;

        self::writeCookie($cookieName, $availableAt);
        $_COOKIE[$cookieName] = (string) $availableAt;

        $status = self::buildStatus($availableAt, time());

        return [
            'available_at' => $status['available_at'],
            'available_at_label' => $status['available_at_label'],
            'remaining_seconds' => $status['remaining_seconds'],
            'remaining_label' => $status['remaining_label'],
        ];
    }

    /**
     * @return array{
     *   locked: bool,
     *   available_at: string,
     *   available_at_label: string,
     *   remaining_seconds: int,
     *   remaining_label: string,
     *   message: string
     * }
     */
    private static function buildStatus(int $availableAt, int $now): array
    {
        $remaining = max(0, $availableAt - $now);
        $label = self::formatDateTime($availableAt);
        $remainingLabel = self::formatRemaining($remaining);

        return [
            'locked' => true,
            'available_at' => date('c', $availableAt),
            'available_at_label' => $label,
            'remaining_seconds' => $remaining,
            'remaining_label' => $remainingLabel,
            'message' => sprintf(
                'You already submitted. Next submission is available after %s (%s remaining).',
                $label,
                $remainingLabel
            ),
        ];
    }

    /**
     * Persist unlock timestamp in the lock cookie.
     */
    private static function writeCookie(string $cookieName, int $availableAt): void
    {
        $options = [
            'expires' => $availableAt,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        // PHP 7.3+ array signature; fall back for unusual hosts.
        if (PHP_VERSION_ID >= 70300) {
            setcookie($cookieName, (string) $availableAt, $options);
            return;
        }

        setcookie($cookieName, (string) $availableAt, $availableAt, '/', '', false, true);
    }

    /**
     * @return int|null Unlock unix timestamp, or null when unparsable / legacy.
     */
    private static function parseUnlockTimestamp(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $value = (int) $raw;
        // Reject tiny legacy values like "1".
        if ($value < 1000000000) {
            return null;
        }

        return $value;
    }

    public static function formatDateTime(int $timestamp): string
    {
        return date('M j, Y \a\t g:i A', $timestamp);
    }

    public static function formatRemaining(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($hours > 0 && $minutes > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        if ($hours > 0) {
            return $hours . ' hour' . ($hours === 1 ? '' : 's');
        }
        if ($minutes > 0) {
            return $minutes . ' minute' . ($minutes === 1 ? '' : 's');
        }

        return $secs . ' second' . ($secs === 1 ? '' : 's');
    }
}
