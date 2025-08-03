<?php

declare(strict_types=1);

namespace App\Infrastructure\User;

use App\Domain\User\UserRepositoryInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class DoctrineUserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findById(int $id): ?User
    {
        $result = $this->find($id);

        return $result instanceof User ? $result : null;
    }

    public function findByEmail(string $email): ?User
    {
        $result = $this->findOneBy(['email' => $email]);

        return $result instanceof User ? $result : null;
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
}
