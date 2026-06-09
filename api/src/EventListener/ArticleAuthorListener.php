<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(entity: Article::class, event: Events::prePersist)]
#[AsEntityListener(entity: Article::class, event: Events::preUpdate)]
final class ArticleAuthorListener
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
        // Dependencies injected via constructor promotion
    }

    public function prePersist(Article $article, LifecycleEventArgs $event): void
    {
        $this->setAuthorIfEmpty($article);
    }

    public function preUpdate(Article $article, LifecycleEventArgs $event): void
    {
        $this->setLastModifiedBy($article);
    }

    private function setAuthorIfEmpty(Article $article): void
    {
        if ($article->getAuthor() !== null) {
            return;
        }

        $currentUser = $this->getCurrentUser();

        if ($currentUser !== null) {
            $article->setAuthor($currentUser);
        }
    }

    private function setLastModifiedBy(Article $article): void
    {
        $currentUser = $this->getCurrentUser();

        if ($currentUser !== null) {
            $article->setLastModifiedBy($currentUser);
        }
    }

    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();

        if (!$user) {
            return null;
        }

        return $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $user->getUserIdentifier(),
        ]);
    }
}
