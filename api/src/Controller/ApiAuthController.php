<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\User\UserRepositoryInterface;
use App\Entity\User;
use App\Infrastructure\User\SymfonyUserAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
final class ApiAuthController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST', 'OPTIONS'])]
    public function login(
        Request $request,
        UserRepositoryInterface $userRepository,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204);
        }

        $credentials = $this->extractCredentials($request);

        if ($credentials instanceof JsonResponse) {
            return $credentials;
        }

        $user = $this->authenticateUser($credentials, $userRepository, $passwordHasher);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        return $this->createSuccessResponse($user);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST', 'OPTIONS'])]
    public function logout(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204);
        }

        return new JsonResponse(['success' => true, 'message' => 'Logged out successfully']);
    }

    #[Route('/status', name: 'api_auth_status', methods: ['GET', 'OPTIONS'])]
    public function status(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204);
        }

        return new JsonResponse([
            'authenticated' => false,
            'message' => 'This endpoint is for stateless authentication only',
        ]);
    }

    /**
     * @return array{email: string, password: string}|JsonResponse
     */
    private function extractCredentials(Request $request): array|JsonResponse
    {
        $data = \json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return new JsonResponse(['error' => 'Email and password are required'], 400);
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
                return new JsonResponse(['error' => 'Email and password are required'], 400);
            }
        }

        return null;
    }

    /**
     * @param array{email: string, password: string} $credentials
     */
    private function authenticateUser(
        array $credentials,
        UserRepositoryInterface $userRepository,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse|User {
        $user = $userRepository->findByEmail($credentials['email']);

        if (!$user) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        $adapter = new SymfonyUserAdapter($user);

        if (!$passwordHasher->isPasswordValid($adapter, $credentials['password'])) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        if (!$user->isAdmin()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        return $user;
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
