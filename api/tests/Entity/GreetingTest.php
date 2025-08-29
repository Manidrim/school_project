<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Greeting;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Entity\Greeting
 */
final class GreetingTest extends TestCase
{
    public function testNewGreetingHasNullId(): void
    {
        $greeting = new Greeting();
        self::assertNull($greeting->getId());
    }

    public function testSetAndGetName(): void
    {
        $greeting = new Greeting();
        $greeting->name = 'Test Name';

        self::assertSame('Test Name', $greeting->name);
    }
}
