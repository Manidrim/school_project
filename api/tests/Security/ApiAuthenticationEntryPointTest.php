<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\ApiAuthenticationEntryPoint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @internal
 *
 * @covers \App\Security\ApiAuthenticationEntryPoint
 */
final class ApiAuthenticationEntryPointTest extends TestCase
{
    private ApiAuthenticationEntryPoint $entryPoint;

    protected function setUp(): void
    {
        $this->entryPoint = new ApiAuthenticationEntryPoint();
    }

    public function testStartReturnsJsonErrorResponse(): void
    {
        $request = new Request();
        $authException = new AuthenticationException('Test exception');

        $response = $this->entryPoint->start($request, $authException);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        self::assertIsString($content);

        $data = \json_decode($content, true);
        self::assertIsArray($data);
        self::assertSame('Authentication required', $data['error']);
        self::assertSame('You must be authenticated to access this resource', $data['message']);
    }

    public function testStartWorksWithoutAuthException(): void
    {
        $request = new Request();

        $response = $this->entryPoint->start($request, null);

        self::assertSame(401, $response->getStatusCode());

        $content = $response->getContent();
        self::assertIsString($content);

        $data = \json_decode($content, true);
        self::assertIsArray($data);
        self::assertSame('Authentication required', $data['error']);
        self::assertSame('You must be authenticated to access this resource', $data['message']);
    }
}
