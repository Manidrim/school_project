<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Simple IP-based rate limiter for login attempts.
 * Uses the filesystem cache to track attempts per IP.
 *
 * Limits: 5 attempts per 5 minutes per IP address.
 */
final class LoginRateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 300; // 5 minutes
    private const CACHE_DIR = '/tmp/login_rate_limit';

    public function isRateLimited(Request $request): bool
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $key = $this->getCacheKey($ip);
        $data = $this->readCache($key);

        if ($data === null) {
            return false;
        }

        // Clean expired entries
        $now = time();
        $attempts = array_filter(
            $data['attempts'],
            static fn(int $timestamp): bool => ($now - $timestamp) < self::WINDOW_SECONDS,
        );

        return \count($attempts) >= self::MAX_ATTEMPTS;
    }

    public function recordAttempt(Request $request): void
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $key = $this->getCacheKey($ip);
        $data = $this->readCache($key) ?? ['attempts' => []];

        $now = time();
        $data['attempts'][] = $now;

        // Keep only attempts within the window
        $data['attempts'] = array_values(array_filter(
            $data['attempts'],
            static fn(int $timestamp): bool => ($now - $timestamp) < self::WINDOW_SECONDS,
        ));

        $this->writeCache($key, $data);
    }

    public function createRateLimitedResponse(): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => 'Too many login attempts. Please try again in a few minutes.',
            'error' => 'rate_limited',
        ], 429);
    }

    private function getCacheKey(string $ip): string
    {
        return hash('sha256', $ip);
    }

    /**
     * @return array{attempts: list<int>}|null
     */
    private function readCache(string $key): ?array
    {
        $file = self::CACHE_DIR . '/' . $key;

        if (!\file_exists($file)) {
            return null;
        }

        $content = \file_get_contents($file);

        if ($content === false) {
            return null;
        }

        $data = \json_decode($content, true);

        return \is_array($data) ? $data : null;
    }

    /**
     * @param array{attempts: list<int>} $data
     */
    private function writeCache(string $key, array $data): void
    {
        if (!\is_dir(self::CACHE_DIR)) {
            \mkdir(self::CACHE_DIR, 0o700, true);
        }

        \file_put_contents(
            self::CACHE_DIR . '/' . $key,
            \json_encode($data),
        );
    }
}
