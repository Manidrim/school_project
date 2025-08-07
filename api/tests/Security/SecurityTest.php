<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 *
 * Tests security configurations and access controls
 *
 * @coversNothing
 */
final class SecurityTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $passwordHasher;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    public function testPublicEndpointsAreAccessible(): void
    {


        $publicEndpoints = [
            '/api/auth/login' => 'POST',
            '/api/auth/status' => 'GET',
            '/login' => 'GET',
        ];

        foreach ($publicEndpoints as $endpoint => $method) {
            $this->client->request($method, $endpoint);
            $statusCode = $this->client->getResponse()->getStatusCode();

            // Should not be redirected (302) or forbidden (403)
            self::assertNotSame(403, $statusCode, "Endpoint {$endpoint} should be accessible");

            if ($method === 'GET') {
                self::assertNotSame(302, $statusCode, "GET endpoint {$endpoint} should not redirect");
            }
        }
    }

    public function testProtectedEndpointsRequireAuthentication(): void
    {


        $protectedEndpoints = [
            '/api/admin',
            '/api/admin/users',
            '/api/admin/content',
            '/api/admin/settings',
            '/admin',
        ];

        foreach ($protectedEndpoints as $endpoint) {
            $this->client->request('GET', $endpoint);
            $statusCode = $this->client->getResponse()->getStatusCode();

            // Should be redirected to login or return 401
            self::assertTrue(
                \in_array($statusCode, [302, 401], true),
                "Protected endpoint {$endpoint} should require authentication (got {$statusCode})",
            );
        }
    }

    public function testAdminEndpointsRequireAdminRole(): void
    {


        // Create a regular user
        $user = $this->createUser('user@test.com', ['ROLE_USER']);

        // Login as regular user
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'user@test.com',
            'password' => 'password123',
        ]));

        $adminEndpoints = [
            '/api/admin',
            '/api/admin/users',
            '/api/admin/content',
            '/api/admin/settings',
        ];

        foreach ($adminEndpoints as $endpoint) {
            $this->client->request('GET', $endpoint, [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

            $this->assertResponseStatusCodeSame(
                403,
                "Admin endpoint {$endpoint} should be forbidden for regular users",
            );
        }
    }

    public function testArticleEndpointsPermissions(): void
    {


        // Test as unauthenticated user
        $this->client->request('GET', '/api/articles');
        $this->assertResponseIsSuccessful('Unauthenticated users should be able to read articles');

        $this->client->request('POST', '/api/articles', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], $this->encodeJsonSafe(['title' => 'Test', 'content' => 'Test']));
        $this->assertResponseStatusCodeSame(401, 'Unauthenticated users should not be able to create articles');

        // Test as regular user
        $user = $this->createUser('user@test.com', ['ROLE_USER']);
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'user@test.com',
            'password' => 'password123',
        ]));

        $this->client->request('POST', '/api/articles', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], $this->encodeJsonSafe(['title' => 'Test', 'content' => 'Test']));
        $this->assertResponseStatusCodeSame(403, 'Regular users should not be able to create articles');

        // Test as admin
        $admin = $this->createUser('admin@test.com', ['ROLE_ADMIN']);
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]));

        $this->client->request('POST', '/api/articles', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], $this->encodeJsonSafe(['title' => 'Admin Article', 'content' => 'Admin content', 'isPublished' => true]));
        $this->assertResponseStatusCodeSame(201, 'Admins should be able to create articles');
    }

    public function testCorsHeaders(): void
    {


        $this->client->request('OPTIONS', '/api/auth/login', [], [], [
            'HTTP_ORIGIN' => 'http://localhost:3000',
        ]);

        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(204);

        // Check CORS headers are present
        self::assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        self::assertTrue($response->headers->has('Access-Control-Allow-Methods'));
        self::assertTrue($response->headers->has('Access-Control-Allow-Headers'));
    }

    public function testSessionPersistence(): void
    {


        // Create admin user
        $this->createUser('admin@test.com', ['ROLE_ADMIN']);

        // Login
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]));

        // First protected request
        $this->client->request('GET', '/api/admin');
        $this->assertResponseIsSuccessful('First request should succeed');

        // Second protected request (should still be authenticated)
        $this->client->request('GET', '/api/admin/users');
        $this->assertResponseIsSuccessful('Second request should succeed with same session');

        // Logout
        $this->client->request('POST', '/api/auth/logout');
        $this->assertResponseIsSuccessful('Logout should succeed');

        // Request after logout should fail
        $this->client->request('GET', '/api/admin');
        $this->assertResponseStatusCodeSame(401, 'Request after logout should require re-authentication');
    }

    public function testPasswordHashing(): void
    {
        // Arrange
        $plainPassword = 'test-password-123';
        $user = new User();

        // Act
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);

        // Assert
        self::assertNotSame($plainPassword, $hashedPassword);
        self::assertTrue($this->passwordHasher->isPasswordValid($user, $plainPassword));
        self::assertFalse($this->passwordHasher->isPasswordValid($user, 'wrong-password'));
    }

    private function cleanDatabase(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\Article')->execute();
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
