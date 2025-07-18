<?php

namespace App\Tests\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        $this->createTestUser();
    }

    private function createTestUser(): void
    {
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneByEmail('admin@test.com');

        if ($existingUser) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }

        $user = new User();
        $user->setEmail('admin@test.com');
        $user->setRoles(['ROLE_ADMIN']);
        
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($user, 'admin123');
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function testLoginPageIsAccessible(): void
    {
        $this->client->request('GET', '/login');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Please sign in');
    }

    public function testValidLoginRedirectsToAdmin(): void
    {
        $crawler = $this->client->request('GET', '/login');
        
        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ]);
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/admin');
    }

    public function testInvalidLoginShowsError(): void
    {
        $crawler = $this->client->request('GET', '/login');
        
        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);
        
        $this->client->submit($form);
        $this->client->followRedirect();
        
        $this->assertSelectorExists('.alert-danger');
    }

    public function testAdminPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin');
        
        $this->assertResponseRedirects('/login');
    }

    public function testAuthenticatedUserCanAccessAdmin(): void
    {
        $this->loginUser();
        
        $this->client->request('GET', '/admin');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Admin Dashboard');
    }

    public function testLogoutRedirectsToLogin(): void
    {
        $this->loginUser();
        
        $this->client->request('GET', '/logout');
        
        $this->assertResponseRedirects();
    }

    private function loginUser(): void
    {
        $crawler = $this->client->request('GET', '/login');
        
        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ]);
        
        $this->client->submit($form);
        $this->client->followRedirect();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->entityManager->close();
        $this->entityManager = null;
    }
} 