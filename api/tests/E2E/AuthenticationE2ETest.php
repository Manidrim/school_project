<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\Tests\Support\ApiAuthTestClient;

/**
 * @internal
 *
 * @coversNothing
 */
final class AuthenticationE2ETest extends ApiTestCase
{
    public function testSuccessfulLogin(): void
    {
        // Arrange
        $admin = $this->createAdminUser();

        ApiAuthTestClient::loginJson($this->client, $admin->getEmail(), 'admin123');

        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertTrue($data['success']);
        self::assertSame('Authentication successful', $data['message']);
        self::assertIsArray($data['user']);
        self::assertSame($admin->getEmail(), $data['user']['email']);
        self::assertIsArray($data['user']['roles']);
        self::assertContains('ROLE_ADMIN', $data['user']['roles']);
    }

    public function testFailedLoginWithInvalidCredentials(): void
    {
        // Arrange
        $this->createAdminUser();

        ApiAuthTestClient::loginJson($this->client, 'admin@test.com', 'wrongpassword');

        $this->assertApiResponseStatusCodeSame(401);
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertFalse($data['success']);
        self::assertSame('Invalid credentials', $data['message']);
    }

    public function testFailedLoginWithNonExistentUser(): void
    {
        ApiAuthTestClient::loginJson($this->client, 'nonexistent@test.com', 'password123');

        $this->assertApiResponseStatusCodeSame(401);
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertFalse($data['success']);
        self::assertSame('Invalid credentials', $data['message']);
    }

    public function testLogout(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Act
        $this->client->request('POST', '/api/auth/logout');

        // Assert
        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertTrue($data['success']);
        self::assertSame('Logged out successfully', $data['message']);
    }

    public function testAccessProtectedEndpointWithoutAuthentication(): void
    {
        // Act
        $this->makeJsonRequest('GET', '/api/admin');

        // Assert
        $this->assertApiResponseStatusCodeSame(302); // Redirect to login
    }

    public function testAccessProtectedEndpointWithAuthentication(): void
    {
        // Arrange
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        // Act
        $this->makeJsonRequest('GET', '/api/admin');

        // Assert
        $this->assertApiResponseIsSuccessful();
        $this->assertJsonResponse();

        $data = $this->decodeJsonResponse();
        self::assertSame('Admin Dashboard', $data['title']);
        self::assertIsArray($data['user']);
        self::assertSame($admin->getEmail(), $data['user']['email']);
    }

    public function testAccessAdminEndpointWithRegularUser(): void
    {
        // Arrange
        $user = $this->createUser('user@test.com', ['ROLE_USER']);
        $this->loginAs($user, 'password123');

        // Act
        $this->makeJsonRequest('GET', '/api/admin');

        // Assert
        $this->assertApiResponseStatusCodeSame(403); // Access denied
    }
}
