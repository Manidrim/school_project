<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\ApiAuthController;
use App\Domain\User\UserRepositoryInterface;
use App\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @internal
 *
 * @covers \App\Controller\ApiAuthController
 */
final class ApiAuthControllerEdgeCasesTest extends WebTestCase
{
    private MockObject&UserRepositoryInterface $userRepository;

    private MockObject&UserPasswordHasherInterface $passwordHasher;

    private MockObject&TokenStorageInterface $tokenStorage;

    private MockObject&SessionInterface $session;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->session = $this->createMock(SessionInterface::class);
    }

    public function testLoginWithInvalidJsonReturns400(): void
    {
        $controller = self::getContainer()->get(ApiAuthController::class);
        $request = new Request(content: '{invalid json}');

        $response = $controller->login(
            $request,
            $this->userRepository,
            $this->passwordHasher,
            $this->tokenStorage,
            $this->session,
        );

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(400, $response->getStatusCode());
    }

    public function testLoginWithMissingFieldsReturns400(): void
    {
        $controller = self::getContainer()->get(ApiAuthController::class);
        $request = new Request(content: (string) \json_encode(['email' => 'foo@example.com']));

        $response = $controller->login(
            $request,
            $this->userRepository,
            $this->passwordHasher,
            $this->tokenStorage,
            $this->session,
        );

        self::assertSame(400, $response->getStatusCode());
    }

    public function testLoginWithInvalidPasswordReturns401(): void
    {
        $controller = self::getContainer()->get(ApiAuthController::class);
        $user = new User('foo@example.com', ['ROLE_USER'], 'hashed');

        $this->userRepository->method('findByEmail')->with('foo@example.com')->willReturn($user);
        $this->passwordHasher->method('isPasswordValid')->willReturn(false);

        $request = new Request(content: (string) \json_encode(['email' => 'foo@example.com', 'password' => 'bad']));

        $response = $controller->login(
            $request,
            $this->userRepository,
            $this->passwordHasher,
            $this->tokenStorage,
            $this->session,
        );

        self::assertSame(401, $response->getStatusCode());
    }

    public function testLoginWithValidCredentialsCreatesTokenAndReturnsSuccess(): void
    {
        $controller = self::getContainer()->get(ApiAuthController::class);
        $user = new User('foo@example.com', ['ROLE_USER'], 'hashed');

        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('isPasswordValid')->willReturn(true);
        $this->tokenStorage->expects(self::once())->method('setToken')->with(
            self::callback(static function ($token): bool {
                return $token instanceof UsernamePasswordToken
                    && \in_array('ROLE_USER', $token->getRoleNames(), true);
            }),
        );
        $this->session->expects(self::once())->method('set')->with(
            '_security_main',
            self::logicalAnd(self::isType('string')),
        );
        $this->session->expects(self::once())->method('save');

        $request = new Request(content: (string) \json_encode(['email' => 'foo@example.com', 'password' => 'ok']));

        $response = $controller->login(
            $request,
            $this->userRepository,
            $this->passwordHasher,
            $this->tokenStorage,
            $this->session,
        );

        self::assertSame(200, $response->getStatusCode());
        $content = (string) $response->getContent();
        self::assertNotSame('', $content);
        $data = \json_decode($content, true);
        self::assertIsArray($data);
        self::assertIsArray($data['user'] ?? null);
        self::assertSame('foo@example.com', $data['user']['email'] ?? null);
    }
}
