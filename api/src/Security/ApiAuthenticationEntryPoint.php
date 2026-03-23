<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $_request, ?AuthenticationException $_authException = null): JsonResponse
    {
        return new JsonResponse([
            'error' => 'Authentication required',
            'message' => 'You must be authenticated to access this resource',
        ], 401);
    }
}
