<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $passwordHasher;

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
        $this->entityManager->close();
        parent::tearDown();
    }

    protected function cleanDatabase(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\Article')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->clear();
    }

    /**
     * @param array<string> $roles
     */
    protected function createUser(string $email, array $roles = ['ROLE_USER'], string $password = 'password123'): User
    {
        $tempUser = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($tempUser, $password);
        $user = new User($email, $roles, $hashedPassword);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createAdminUser(string $email = 'admin@test.com', string $password = 'admin123'): User
    {
        return $this->createUser($email, ['ROLE_ADMIN'], $password);
    }

    protected function loginAs(User $user, string $password = 'admin123'): void
    {
        $jsonContent = $this->encodeJson([
            'email' => $user->getEmail(),
            'password' => $password,
        ]);

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $jsonContent);

        $this->assertApiResponseIsSuccessful();
    }

    protected function logout(): void
    {
        $this->client->request('POST', '/api/auth/logout');
    }

    protected function assertApiResponseIsSuccessful(): void
    {
        self::assertLessThan(400, $this->client->getResponse()->getStatusCode());
    }

    protected function assertApiResponseStatusCodeSame(int $expectedCode): void
    {
        self::assertSame($expectedCode, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getResponseData(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);

        $decoded = \json_decode($content, true);
        self::assertIsArray($decoded);

        return $decoded;
    }

    protected function assertJsonResponse(): void
    {
        $response = $this->client->getResponse();
        $contentType = (string) $response->headers->get('Content-Type');
        self::assertTrue(
            \str_contains($contentType, 'application/ld+json')
            || \str_contains($contentType, 'application/json')
            || \str_contains($contentType, 'application/problem+json'),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function makeJsonRequest(string $method, string $uri, array $data = []): void
    {
        $jsonContent = empty($data) ? null : $this->encodeJson($data);

        $this->client->request($method, $uri, [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $jsonContent);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function encodeJson(array $data): string
    {
        $encoded = \json_encode($data);
        self::assertNotFalse($encoded);

        return $encoded;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJsonResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $decoded = \json_decode($content, true);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
