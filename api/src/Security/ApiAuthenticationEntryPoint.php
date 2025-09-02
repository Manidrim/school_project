<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $acceptHeader = (string) $request->headers->get('Accept', '');

        // For API Platform (application/ld+json), redirect to login like form_login behaviour
        if (\str_contains($acceptHeader, 'application/ld+json') || \str_contains($acceptHeader, 'text/html')) {
            return new RedirectResponse('/login', 302);
        }

        return new JsonResponse([
            'error' => 'Authentication required',
            'message' => 'You must be authenticated to access this resource',
        ], 401);
    }
}
