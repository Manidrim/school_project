<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\User\UserRepositoryInterface;
use App\Entity\User;
use App\Infrastructure\User\SymfonyUserAdapter;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 *
 * @covers \App\Controller\ApiAdminController
 */
final class ApiAdminControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->userRepository = self::getContainer()->get(UserRepositoryInterface::class);

        $this->createTestUser();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testDashboardOptionsRequest(): void
    {
        $this->client->request('OPTIONS', '/api/admin');
        $this->assertResponseStatusCodeSame(204);
    }

    public function testDashboardRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/admin', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseStatusCodeSame(401); // Authentication required
    }

    public function testDashboardWithAuthentication(): void
    {
        $user = $this->userRepository->findByEmail('admin@test.com');
        self::assertNotNull($user, 'Admin user should exist');
        $adapter = new SymfonyUserAdapter($user);
        $this->client->loginUser($adapter);

        $this->client->request('GET', '/api/admin', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();

        self::assertSame('Admin Dashboard', $response['title']);
        self::assertIsArray($response['user']);
        self::assertSame('admin@test.com', $response['user']['email']);
        self::assertArrayHasKey('modules', $response);
        self::assertArrayHasKey('stats', $response);
    }

    public function testUsersOptionsRequest(): void
    {
        $this->client->request('OPTIONS', '/api/admin/users');
        $this->assertResponseStatusCodeSame(204);
    }

    public function testUsersRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/admin/users', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUsersWithAuthentication(): void
    {
        $user = $this->userRepository->findByEmail('admin@test.com');
        self::assertNotNull($user, 'Admin user should exist');
        $adapter = new SymfonyUserAdapter($user);
        $this->client->loginUser($adapter);

        $this->client->request('GET', '/api/admin/users', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();

        self::assertArrayHasKey('users', $response);
        self::assertArrayHasKey('total', $response);
        self::assertGreaterThanOrEqual(1, $response['total']);
    }

    public function testContentOptionsRequest(): void
    {
        $this->client->request('OPTIONS', '/api/admin/content');
        $this->assertResponseStatusCodeSame(204);
    }

    public function testContentRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/admin/content', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testContentWithAuthentication(): void
    {
        $user = $this->userRepository->findByEmail('admin@test.com');
        self::assertNotNull($user, 'Admin user should exist');
        $adapter = new SymfonyUserAdapter($user);
        $this->client->loginUser($adapter);

        $this->client->request('GET', '/api/admin/content', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();

        self::assertArrayHasKey('content_types', $response);
        self::assertSame('Content management endpoint', $response['message']);
    }

    public function testSettingsOptionsRequest(): void
    {
        $this->client->request('OPTIONS', '/api/admin/settings');
        $this->assertResponseStatusCodeSame(204);
    }

    public function testSettingsRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/admin/settings', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testSettingsWithAuthentication(): void
    {
        $user = $this->userRepository->findByEmail('admin@test.com');
        self::assertNotNull($user, 'Admin user should exist');
        $adapter = new SymfonyUserAdapter($user);
        $this->client->loginUser($adapter);

        $this->client->request('GET', '/api/admin/settings', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();

        self::assertArrayHasKey('settings', $response);
        self::assertSame('System settings endpoint', $response['message']);
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

    private function createTestUser(): void
    {
        $existingUser = $this->userRepository->findByEmail('admin@test.com');

        if ($existingUser) {
            $this->userRepository->remove($existingUser);
        }

        $user = new User('admin@test.com', ['ROLE_ADMIN']);
        $adapter = new SymfonyUserAdapter($user);

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($adapter, 'admin123');
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
    }
}
