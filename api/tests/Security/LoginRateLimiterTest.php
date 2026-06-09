<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\LoginRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @covers \App\Security\LoginRateLimiter
 */
final class LoginRateLimiterTest extends TestCase
{
    private const CACHE_DIR = '/tmp/login_rate_limit';

    protected function setUp(): void
    {
        $this->purgeCacheDir();
    }

    protected function tearDown(): void
    {
        $this->purgeCacheDir();
    }

    public function testIsRateLimitedReturnsFalseInTestEnvironment(): void
    {
        $limiter = new LoginRateLimiter('test');

        self::assertFalse($limiter->isRateLimited($this->createRequest('192.0.2.1')));
    }

    public function testRecordAttemptDoesNothingInTestEnvironment(): void
    {
        $limiter = new LoginRateLimiter('test');

        $limiter->recordAttempt($this->createRequest('192.0.2.2'));

        self::assertFileDoesNotExist($this->cacheFileForIp('192.0.2.2'));
    }

    public function testCreateRateLimitedResponseReturns429(): void
    {
        $response = (new LoginRateLimiter('prod'))->createRateLimitedResponse();

        self::assertSame(429, $response->getStatusCode());
        $data = \json_decode((string) $response->getContent(), true);
        self::assertIsArray($data);
        self::assertFalse($data['success'] ?? null);
        self::assertSame('rate_limited', $data['error'] ?? null);
    }

    public function testIsRateLimitedReturnsFalseWithoutRecordedAttempts(): void
    {
        $limiter = new LoginRateLimiter('prod');

        self::assertFalse($limiter->isRateLimited($this->createRequest('192.0.2.3')));
    }

    public function testFewerAttemptsThanLimitAreNotRateLimited(): void
    {
        $limiter = new LoginRateLimiter('prod');
        $request = $this->createRequest('192.0.2.4');

        for ($i = 0; $i < 4; ++$i) {
            $limiter->recordAttempt($request);
        }

        self::assertFalse($limiter->isRateLimited($request));
    }

    public function testReachingMaxAttemptsTriggersRateLimit(): void
    {
        $limiter = new LoginRateLimiter('prod');
        $request = $this->createRequest('192.0.2.5');

        for ($i = 0; $i < 5; ++$i) {
            $limiter->recordAttempt($request);
        }

        self::assertTrue($limiter->isRateLimited($request));
    }

    public function testExpiredAttemptsAreIgnored(): void
    {
        $limiter = new LoginRateLimiter('prod');
        $expired = \time() - 3600;
        $this->writeCacheFileForIp('192.0.2.6', (string) \json_encode([
            'attempts' => [$expired, $expired, $expired, $expired, $expired],
        ]));

        self::assertFalse($limiter->isRateLimited($this->createRequest('192.0.2.6')));
    }

    public function testExpiredAttemptsArePrunedWhenRecording(): void
    {
        $limiter = new LoginRateLimiter('prod');
        $expired = \time() - 3600;
        $this->writeCacheFileForIp('192.0.2.7', (string) \json_encode([
            'attempts' => [$expired, $expired, $expired, $expired, $expired],
        ]));

        $limiter->recordAttempt($this->createRequest('192.0.2.7'));

        self::assertFalse($limiter->isRateLimited($this->createRequest('192.0.2.7')));
    }

    public function testNonIntegerAttemptsAreFiltered(): void
    {
        $limiter = new LoginRateLimiter('prod');
        $this->writeCacheFileForIp('192.0.2.8', (string) \json_encode([
            'attempts' => ['foo', null, \time(), \time()],
        ]));

        self::assertFalse($limiter->isRateLimited($this->createRequest('192.0.2.8')));
    }

    public function testCorruptedCacheFileIsIgnored(): void
    {
        $limiter = new LoginRateLimiter('prod');
        $this->writeCacheFileForIp('192.0.2.9', 'not valid json');

        self::assertFalse($limiter->isRateLimited($this->createRequest('192.0.2.9')));
    }

    public function testCacheFileWithoutAttemptsKeyIsIgnored(): void
    {
        $limiter = new LoginRateLimiter('prod');
        $this->writeCacheFileForIp('192.0.2.10', '{"other":[]}');

        self::assertFalse($limiter->isRateLimited($this->createRequest('192.0.2.10')));
    }

    private function createRequest(string $ip): Request
    {
        return Request::create('/api/auth/login', 'POST', server: ['REMOTE_ADDR' => $ip]);
    }

    private function cacheFileForIp(string $ip): string
    {
        return self::CACHE_DIR . '/' . \hash('sha256', $ip);
    }

    private function writeCacheFileForIp(string $ip, string $content): void
    {
        if (!\is_dir(self::CACHE_DIR)) {
            \mkdir(self::CACHE_DIR, 0o700, true);
        }

        \file_put_contents($this->cacheFileForIp($ip), $content);
    }

    private function purgeCacheDir(): void
    {
        if (!\is_dir(self::CACHE_DIR)) {
            return;
        }

        $files = \glob(self::CACHE_DIR . '/*');

        foreach ($files === false ? [] : $files as $file) {
            \unlink($file);
        }

        \rmdir(self::CACHE_DIR);
    }
}
