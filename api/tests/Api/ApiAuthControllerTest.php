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
 * @covers \App\Controller\ApiAuthController
 */
final class ApiAuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->userRepository = self::getContainer()->get(UserRepositoryInterface::class);

        $this->createTestUsers();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testLoginOptionsRequest(): void
    {
        $this->client->request('OPTIONS', '/api/auth/login');
        $this->assertResponseStatusCodeSame(204);
    }

    public function testLoginWithValidAdminCredentials(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) \json_encode([
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ]));

        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();

        self::assertTrue($response['success']);
        self::assertSame('Authentication successful', $response['message']);
        self::assertIsArray($response['user']);
        self::assertSame('admin@test.com', $response['user']['email']);
        self::assertIsArray($response['user']['roles']);
        self::assertContains('ROLE_ADMIN', $response['user']['roles']);
    }

    public function testLoginWithMissingEmail(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) \json_encode([
            'password' => 'admin123',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = $this->decodeJsonResponse();
        self::assertSame('Email and password are required', $response['error']);
    }

    public function testLoginWithMissingPassword(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) \json_encode([
            'email' => 'admin@test.com',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = $this->decodeJsonResponse();
        self::assertSame('Email and password are required', $response['error']);
    }

    public function testLoginWithInvalidEmail(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) \json_encode([
            'email' => 'nonexistent@test.com',
            'password' => 'admin123',
        ]));

        $this->assertResponseStatusCodeSame(401);
        $response = $this->decodeJsonResponse();
        self::assertSame('Invalid credentials', $response['error']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) \json_encode([
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]));

        $this->assertResponseStatusCodeSame(401);
        $response = $this->decodeJsonResponse();
        self::assertSame('Invalid credentials', $response['error']);
    }

    public function testLoginWithNonAdminUser(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) \json_encode([
            'email' => 'user@test.com',
            'password' => 'user123',
        ]));

        $this->assertResponseStatusCodeSame(403);
        $response = $this->decodeJsonResponse();
        self::assertSame('Access denied', $response['error']);
    }

    public function testLoginWithInvalidJson(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $this->assertResponseStatusCodeSame(400);
        $response = $this->decodeJsonResponse();
        self::assertSame('Email and password are required', $response['error']);
    }

    public function testLogoutOptionsRequest(): void
    {
        $this->client->request('OPTIONS', '/api/auth/logout');
        $this->assertResponseStatusCodeSame(204);
    }

    public function testLogoutPostRequest(): void
    {
        $this->client->request('POST', '/api/auth/logout');
        $this->assertResponseIsSuccessful();
        $response = $this->decodeJsonResponse();
        self::assertSame('Logged out successfully', $response['message']);
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

    private function createTestUsers(): void
    {
        $emails = ['admin@test.com', 'user@test.com'];

        foreach ($emails as $email) {
            $existingUser = $this->userRepository->findByEmail($email);

            if ($existingUser) {
                $this->userRepository->remove($existingUser);
            }
        }

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $adminUser = new User('admin@test.com', ['ROLE_ADMIN']);
        $adminAdapter = new SymfonyUserAdapter($adminUser);
        $hashedPassword = $passwordHasher->hashPassword($adminAdapter, 'admin123');
        $adminUser->setPassword($hashedPassword);
        $this->userRepository->save($adminUser);

        $regularUser = new User('user@test.com', ['ROLE_USER']);
        $regularAdapter = new SymfonyUserAdapter($regularUser);
        $hashedPassword = $passwordHasher->hashPassword($regularAdapter, 'user123');
        $regularUser->setPassword($hashedPassword);
        $this->userRepository->save($regularUser);
    }
}
