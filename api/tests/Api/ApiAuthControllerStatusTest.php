<?php

declare(strict_types=1);

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @covers \App\Controller\ApiAuthController
 */
final class ApiAuthControllerStatusTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testStatusOptionsRequest(): void
    {
        $this->client->request('OPTIONS', '/api/auth/status');
        $this->assertResponseStatusCodeSame(204);
    }

    public function testStatusGetRequest(): void
    {
        $this->client->request('GET', '/api/auth/status');
        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();

        self::assertFalse($response['authenticated']);
        self::assertSame('This endpoint is for stateless authentication only', $response['message']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        $response = \json_decode($content ?: '', true);

        if (!\is_array($response)) {
            self::fail('Response is not a valid JSON array');
        }

        return $response;
    }
}
