<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\ApiAuthenticationEntryPoint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @covers \App\Security\ApiAuthenticationEntryPoint
 */
final class ApiAuthenticationEntryPointRedirectTest extends TestCase
{
    public function testStartRedirectsForLdJsonAccept(): void
    {
        $entryPoint = new ApiAuthenticationEntryPoint();
        $request = new Request();
        $request->headers->set('Accept', 'application/ld+json');

        $response = $entryPoint->start($request, null);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('Location'));
    }
}
