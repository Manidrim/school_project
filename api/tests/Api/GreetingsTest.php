<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

/**
 * @internal
 *
 * @covers \App\Entity\Greeting
 */
final class GreetingsTest extends ApiTestCase
{
    public function testCreateGreeting(): void
    {
        self::createClient()->request('POST', '/greetings', [
            'json' => [
                'name' => 'Kévin',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/Greeting',
            '@type' => 'Greeting',
            'name' => 'Kévin',
        ]);
    }
}
