<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\User\SymfonyUserAdapter;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

final class SymfonyPasswordUpgrader implements PasswordUpgraderInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof SymfonyUserAdapter) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $domainUser = $user->getUser();
        $domainUser->setPassword($newHashedPassword);

        $this->userRepository->save($domainUser);
    }
}
