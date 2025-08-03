<?php

declare(strict_types=1);

namespace App\Tests;

use App\Kernel;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Kernel
 */
final class KernelTest extends TestCase
{
    public function testKernelCanBeCreated(): void
    {
        $kernel = new Kernel('test', true);

        self::assertInstanceOf(Kernel::class, $kernel);
        self::assertSame('test', $kernel->getEnvironment());
        self::assertTrue($kernel->isDebug());
    }

    public function testKernelInProductionMode(): void
    {
        $kernel = new Kernel('prod', false);

        self::assertSame('prod', $kernel->getEnvironment());
        self::assertFalse($kernel->isDebug());
    }
}
