<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse([
            'error' => 'Authentication required',
            'message' => 'You must be authenticated to access this resource'
        ], 401);
    }
} 