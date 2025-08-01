<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin')]
class ApiAdminController extends AbstractController
{
    #[Route('', name: 'api_admin_dashboard', methods: ['GET', 'OPTIONS'])]
    public function dashboard(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204);
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse([
            'title' => 'Admin Dashboard',
            'message' => 'Welcome to the administration panel',
            'user' => [
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ],
            'modules' => [
                [
                    'id' => 'users',
                    'title' => 'User Management',
                    'description' => 'Manage application users and permissions',
                    'icon' => 'users',
                    'url' => '/api/admin/users'
                ],
                [
                    'id' => 'content',
                    'title' => 'Content Management', 
                    'description' => 'Manage application content and settings',
                    'icon' => 'content',
                    'url' => '/api/admin/content'
                ],
                [
                    'id' => 'settings',
                    'title' => 'System Settings',
                    'description' => 'Configure application settings',
                    'icon' => 'settings',
                    'url' => '/api/admin/settings'
                ]
            ],
            'stats' => [
                'total_users' => 2,
                'last_login' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('/users', name: 'api_admin_users', methods: ['GET', 'OPTIONS'])]
    public function users(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204);
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $entityManager->getRepository(User::class)->findAll();
        $usersData = [];

        foreach ($users as $user) {
            $usersData[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ];
        }

        return new JsonResponse([
            'users' => $usersData,
            'total' => count($usersData)
        ]);
    }

    #[Route('/content', name: 'api_admin_content', methods: ['GET', 'OPTIONS'])]
    public function content(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204);
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return new JsonResponse([
            'message' => 'Content management endpoint',
            'content_types' => [
                ['id' => 'posts', 'name' => 'Blog Posts', 'count' => 0],
                ['id' => 'pages', 'name' => 'Static Pages', 'count' => 0],
                ['id' => 'media', 'name' => 'Media Files', 'count' => 0]
            ]
        ]);
    }

    #[Route('/settings', name: 'api_admin_settings', methods: ['GET', 'OPTIONS'])]
    public function settings(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204);
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return new JsonResponse([
            'message' => 'System settings endpoint',
            'settings' => [
                ['key' => 'site_name', 'value' => 'My Blog', 'type' => 'text'],
                ['key' => 'maintenance_mode', 'value' => false, 'type' => 'boolean'],
                ['key' => 'max_upload_size', 'value' => '10MB', 'type' => 'text']
            ]
        ]);
    }
} 