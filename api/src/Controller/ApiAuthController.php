<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/api/auth')]
class ApiAuthController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST', 'OPTIONS'])]
    public function login(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Email and password are required'], 400);
        }

        $user = $entityManager->getRepository(User::class)->findOneByEmail($data['email']);
        
        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Authentication successful',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]
        ]);
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
            'message' => 'This endpoint is for stateless authentication only'
        ]);
    }
} 