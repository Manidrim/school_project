<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 *
 * @covers \App\Controller\ApiAuthController
 */
final class ApiAuthControllerCompleteTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    public function testLoginOptionsRequest(): void
    {
        // Act
        $this->client->request('OPTIONS', '/api/auth/login');

        // Assert
        $this->assertResponseStatusCodeSame(204);
    }

    public function testLoginWithValidCredentials(): void
    {
        // Arrange
        $this->createUser('admin@test.com', ['ROLE_ADMIN'], 'admin123');

        // Act
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ]));

        // Assert
        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();

        self::assertTrue($response['success']);
        self::assertSame('Authentication successful', $response['message']);
        self::assertIsArray($response['user']);
        self::assertSame('admin@test.com', $response['user']['email']);
        self::assertIsArray($response['user']['roles']);
        self::assertContains('ROLE_ADMIN', $response['user']['roles']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        // Arrange
        $this->createUser('admin@test.com', ['ROLE_ADMIN'], 'admin123');

        // Act
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]));

        // Assert
        $this->assertResponseStatusCodeSame(401);
        $response = $this->decodeJsonResponse();

        self::assertFalse($response['success']);
        self::assertSame('Invalid credentials', $response['message']);
    }

    public function testLoginWithNonExistentUser(): void
    {
        // Act
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'nonexistent@test.com',
            'password' => 'password123',
        ]));

        // Assert
        $this->assertResponseStatusCodeSame(401);
        $response = $this->decodeJsonResponse();

        self::assertFalse($response['success']);
        self::assertSame('Invalid credentials', $response['message']);
    }

    public function testLoginWithMissingEmail(): void
    {
        // Act
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'password' => 'password123',
        ]));

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $response = $this->decodeJsonResponse();

        self::assertFalse($response['success']);
        self::assertSame('Email and password are required', $response['message']);
    }

    public function testLoginWithMissingPassword(): void
    {
        // Act
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'test@test.com',
        ]));

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $response = $this->decodeJsonResponse();

        self::assertFalse($response['success']);
        self::assertSame('Email and password are required', $response['message']);
    }

    public function testLoginWithInvalidJson(): void
    {
        // Act
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{invalid json}');

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $response = $this->decodeJsonResponse();

        self::assertFalse($response['success']);
        self::assertSame('Email and password are required', $response['message']);
    }

    public function testLogoutOptionsRequest(): void
    {
        // Act
        $this->client->request('OPTIONS', '/api/auth/logout');

        // Assert
        $this->assertResponseStatusCodeSame(204);
    }

    public function testLogout(): void
    {
        // Arrange - Login first
        $this->createUser('admin@test.com', ['ROLE_ADMIN'], 'admin123');
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ]));

        // Act
        $this->client->request('POST', '/api/auth/logout');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();

        self::assertTrue($response['success']);
        self::assertSame('Logged out successfully', $response['message']);
    }

    public function testStatusEndpointWithoutAuthentication(): void
    {
        // Act
        $this->client->request('GET', '/api/auth/status');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();

        self::assertFalse($response['authenticated']);
        self::assertNull($response['user']);
    }

    public function testStatusEndpointWithAuthentication(): void
    {
        // Arrange - Login first
        $this->createUser('admin@test.com', ['ROLE_ADMIN'], 'admin123');
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ]));

        // Act
        $this->client->request('GET', '/api/auth/status');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();

        self::assertTrue($response['authenticated']);
        self::assertNotNull($response['user']);
        self::assertIsArray($response['user']);
        self::assertSame('admin@test.com', $response['user']['email']);
    }

    public function testAuthenticationPersistsAcrossRequests(): void
    {
        // Arrange - Login
        $this->createUser('admin@test.com', ['ROLE_ADMIN'], 'admin123');
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ]));

        // Act - Make a protected request
        $this->client->request('GET', '/api/admin', [], [], [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();
        self::assertSame('Admin Dashboard', $response['title']);
    }

    private function cleanDatabase(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->clear();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encodeJsonSafe(array $data): string
    {
        $encoded = \json_encode($data);
        self::assertNotFalse($encoded);

        return $encoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $decoded = \json_decode($content, true);
        self::assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @param array<string> $roles
     */
    private function createUser(string $email, array $roles = ['ROLE_USER'], string $password = 'password123'): User
    {
        $tempUser = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($tempUser, $password);
        $user = new User($email, $roles, $hashedPassword);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
