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
 * @covers \App\Controller\ApiAdminController
 */
final class ApiAdminControllerCompleteTest extends WebTestCase
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

    public function testDashboardOptionsRequest(): void
    {
        $this->client->request('OPTIONS', '/api/admin');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDashboardWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/admin');

        $this->assertResponseStatusCodeSame(401); // Authentication required
    }

    public function testDashboardWithRegularUser(): void
    {

        $this->createUser('user@test.com', ['ROLE_USER']);

        $jsonContent = \json_encode([
            'email' => 'user@test.com',
            'password' => 'password123',
        ]);
        self::assertNotFalse($jsonContent);

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $jsonContent);

        $this->client->request('GET', '/api/admin');

        $this->assertResponseStatusCodeSame(403); // Access denied
    }

    public function testDashboardWithAdmin(): void
    {

        $this->createUser('admin@test.com', ['ROLE_ADMIN']);

        $jsonContent = \json_encode([
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);
        self::assertNotFalse($jsonContent);

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $jsonContent);

        $this->client->request('GET', '/api/admin', [], [], [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = \json_decode($content, true);
        self::assertIsArray($response);

        self::assertSame('Admin Dashboard', $response['title']);
        self::assertSame('Welcome to the administration panel', $response['message']);
        self::assertArrayHasKey('user', $response);
        self::assertArrayHasKey('modules', $response);
        self::assertArrayHasKey('stats', $response);
    }

    public function testUsersEndpointWithAdmin(): void
    {

        $this->createUser('admin@test.com', ['ROLE_ADMIN']);
        $this->createUser('user1@test.com', ['ROLE_USER']);
        $this->createUser('user2@test.com', ['ROLE_USER']);

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]));

        $this->client->request('GET', '/api/admin/users', [], [], [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = \json_decode($content, true);
        self::assertIsArray($response);

        self::assertSame('User Management', $response['title']);
        self::assertArrayHasKey('users', $response);
        self::assertCount(3, $response['users']); // admin + 2 users
    }

    public function testContentEndpointWithAdmin(): void
    {

        $this->createUser('admin@test.com', ['ROLE_ADMIN']);

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]));

        $this->client->request('GET', '/api/admin/content', [], [], [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = \json_decode($content, true);
        self::assertIsArray($response);

        self::assertSame('Content Management', $response['title']);
        self::assertArrayHasKey('content', $response);
    }

    public function testSettingsEndpointWithAdmin(): void
    {

        $this->createUser('admin@test.com', ['ROLE_ADMIN']);

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]));

        $this->client->request('GET', '/api/admin/settings', [], [], [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = \json_decode($content, true);
        self::assertIsArray($response);

        self::assertSame('System Settings', $response['title']);
        self::assertArrayHasKey('settings', $response);
    }

    public function testDashboardStatistics(): void
    {

        $admin = $this->createUser('admin@test.com', ['ROLE_ADMIN']);
        $this->createUser('user1@test.com', ['ROLE_USER']);
        $this->createUser('user2@test.com', ['ROLE_USER']);

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]));

        $this->client->request('GET', '/api/admin', [], [], [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = \json_decode($content, true);
        self::assertIsArray($response);

        self::assertSame(3, $response['stats']['total_users']);
        self::assertArrayHasKey('last_login', $response['stats']);
    }

    public function testDashboardModules(): void
    {

        $this->createUser('admin@test.com', ['ROLE_ADMIN']);

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $this->encodeJsonSafe([
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]));

        $this->client->request('GET', '/api/admin', [], [], [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = \json_decode($content, true);
        self::assertIsArray($response);

        self::assertIsArray($response['modules']);
        self::assertCount(3, $response['modules']);

        $moduleIds = \array_column($response['modules'], 'id');
        self::assertContains('users', $moduleIds);
        self::assertContains('content', $moduleIds);
        self::assertContains('settings', $moduleIds);

        foreach ($response['modules'] as $module) {
            self::assertArrayHasKey('title', $module);
            self::assertArrayHasKey('description', $module);
            self::assertArrayHasKey('icon', $module);
            self::assertArrayHasKey('url', $module);
        }
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
