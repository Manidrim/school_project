<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\User\UserRepositoryInterface;
use App\Entity\User;
use App\Infrastructure\User\SymfonyUserAdapter;
use App\Security\LoginRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/api/auth')]
final class ApiAuthController extends AbstractController
{
    public function __construct(
        private readonly LoginRateLimiter $rateLimiter,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/csrf-token', name: 'api_csrf_token', methods: ['GET'])]
    public function csrfToken(): JsonResponse
    {
        $token = $this->csrfTokenManager->getToken('api_auth');

        return new JsonResponse(['csrf_token' => $token->getValue()]);
    }

    #[Route('/login', name: 'api_login', methods: ['POST', 'OPTIONS'])]
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function login(
        Request $request,
        UserRepositoryInterface $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage,
        SessionInterface $session,
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204);
        }

        // Rate limiting check
        if ($this->rateLimiter->isRateLimited($request)) {
            return $this->rateLimiter->createRateLimitedResponse();
        }

        $credentials = $this->extractCredentials($request);

        if ($credentials instanceof JsonResponse) {
            return $credentials;
        }

        // CSRF validation
        $csrfError = $this->validateCsrfToken($request);
        if ($csrfError instanceof JsonResponse) {
            return $csrfError;
        }

        $user = $this->resolveAuthenticatedUser($credentials, $userRepository, $passwordHasher);

        if ($user instanceof JsonResponse) {
            // Record failed attempt for rate limiting
            $this->rateLimiter->recordAttempt($request);

            return $user;
        }

        // Create authentication token and session manually
        $userAdapter = new SymfonyUserAdapter($user);
        $token = new UsernamePasswordToken($userAdapter, 'main', $userAdapter->getRoles());
        $tokenStorage->setToken($token);

        // Store the token in session for persistence
        $session->set('_security_main', \serialize($token));
        $session->save();

        return $this->createSuccessResponse($user);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST', 'OPTIONS'])]
    public function logout(
        Request $request,
        TokenStorageInterface $tokenStorage,
        SessionInterface $session,
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204);
        }

        // Clear authentication token and session
        $tokenStorage->setToken(null);
        $session->remove('_security_main');
        $session->invalidate();

        return new JsonResponse(['success' => true, 'message' => 'Logged out successfully']);
    }

    #[Route('/status', name: 'api_auth_status', methods: ['GET', 'OPTIONS'])]
    public function status(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204);
        }

        $user = $this->getUser();

        if ($user instanceof SymfonyUserAdapter) {
            $domainUser = $user->getUser();

            return new JsonResponse([
                'authenticated' => true,
                'user' => [
                    'email' => $domainUser->getEmail(),
                    'roles' => $domainUser->getRoles(),
                ],
            ]);
        }

        return new JsonResponse([
            'authenticated' => false,
            'message' => 'This endpoint is for stateless authentication only',
            'user' => null,
        ]);
    }

    /**
     * @return array{email: string, password: string}|JsonResponse
     */
    private function extractCredentials(Request $request): array|JsonResponse
    {
        $data = \json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Email and password are required',
                'error' => 'Email and password are required',
            ], 400);
        }

        $validationError = $this->validateCredentialsData($data);

        if ($validationError instanceof JsonResponse) {
            return $validationError;
        }

        return ['email' => $data['email'], 'password' => $data['password']];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateCredentialsData(array $data): ?JsonResponse
    {
        $requiredFields = ['email', 'password'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || !\is_string($data[$field])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Email and password are required',
                    'error' => 'Email and password are required',
                ], 400);
            }
        }

        return null;
    }

    /**
     * @param array{email: string, password: string} $credentials
     */
    private function resolveAuthenticatedUser(
        array $credentials,
        UserRepositoryInterface $userRepository,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse|User {
        $user = $userRepository->findByEmail($credentials['email']);

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid credentials',
                'error' => 'Invalid credentials',
            ], 401);
        }

        $adapter = new SymfonyUserAdapter($user);

        if (!$passwordHasher->isPasswordValid($adapter, $credentials['password'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid credentials',
                'error' => 'Invalid credentials',
            ], 401);
        }

        return $user;
    }

    private function validateCsrfToken(Request $request): ?JsonResponse
    {
        $data = \json_decode($request->getContent(), true);
        $csrfToken = \is_array($data) ? ($data['_csrf_token'] ?? null) : null;

        if (!\is_string($csrfToken)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'CSRF token is missing',
                'error' => 'csrf_token_missing',
            ], 403);
        }

        $token = new CsrfToken('api_auth', $csrfToken);

        if (!$this->csrfTokenManager->isTokenValid($token)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid CSRF token',
                'error' => 'csrf_token_invalid',
            ], 403);
        }

        return null;
    }

    private function createSuccessResponse(User $user): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'Authentication successful',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }
}
