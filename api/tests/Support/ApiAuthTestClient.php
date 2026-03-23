<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class ApiAuthTestClient
{
    public static function loginJson(KernelBrowser $client, string $email, string $password): void
    {
        $csrf = self::fetchCsrfTokenValue($client);
        $body = self::encodeLoginPayload($email, $password, $csrf);
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    public static function fetchCsrfTokenValue(KernelBrowser $client): string
    {
        $client->request('GET', '/api/auth/csrf-token');
        Assert::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        Assert::assertNotFalse($content);
        $decoded = \json_decode($content, true);
        Assert::assertIsArray($decoded);
        Assert::assertArrayHasKey('csrf_token', $decoded);
        Assert::assertIsString($decoded['csrf_token']);

        return $decoded['csrf_token'];
    }

    private static function encodeLoginPayload(string $email, string $password, string $csrfToken): string
    {
        $encoded = \json_encode([
            'email' => $email,
            'password' => $password,
            '_csrf_token' => $csrfToken,
        ]);
        Assert::assertNotFalse($encoded);

        return $encoded;
    }
}
