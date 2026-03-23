<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\User\SymfonyUserAdapter;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SymfonyUserAdapter) {
            throw new UnsupportedUserException(\sprintf('Instance of "%s" is not supported.', $user::class));
        }

        $domainUser = $this->userRepository->findByEmail($user->getUserIdentifier());

        if ($domainUser === null) {
            throw new UserNotFoundException(\sprintf('User "%s" not found.', $user->getUserIdentifier()));
        }

        return new SymfonyUserAdapter($domainUser);
    }

    public function supportsClass(string $class): bool
    {
        return $class === SymfonyUserAdapter::class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findByEmail($identifier);

        if ($user === null) {
            throw new UserNotFoundException(\sprintf('User "%s" not found.', $identifier));
        }

        return new SymfonyUserAdapter($user);
    }
}
