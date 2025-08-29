<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'articles')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['article:read']]),
        new Get(normalizationContext: ['groups' => ['article:read']]),
        new Post(
            denormalizationContext: ['groups' => ['article:write']],
            security: "is_granted('ROLE_ADMIN')",
        ),
        new Put(
            denormalizationContext: ['groups' => ['article:write']],
            security: "is_granted('ROLE_ADMIN')",
        ),
        new Patch(
            denormalizationContext: ['groups' => ['article:write']],
            security: "is_granted('ROLE_ADMIN')",
        ),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['article:read']],
    denormalizationContext: ['groups' => ['article:write']],
)]
/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
final class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['article:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['article:read', 'article:write'])]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, precision: 6)]
    #[Groups(['article:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, precision: 6)]
    #[Groups(['article:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['article:read'])]
    private ?User $author = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['article:read'])]
    private ?User $lastModifiedBy = null;

    #[ORM\Column(name: 'is_published')]
    #[Groups(['article:read', 'article:write'])]
    #[Assert\Type('bool', groups: ['article:write'])]
    private bool $isPublished = false;

    public function __construct(
        ?string $title = null,
        ?string $content = null,
        ?User $author = null,
    ) {
        $this->title = $title;
        $this->content = $content;
        $this->author = $author;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        $this->updateModifiedTimestamp();

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        $this->updateModifiedTimestamp();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getLastModifiedBy(): ?User
    {
        return $this->lastModifiedBy;
    }

    public function setLastModifiedBy(?User $lastModifiedBy): self
    {
        $this->lastModifiedBy = $lastModifiedBy;
        $this->updateModifiedTimestamp();

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function getIsPublished(): bool
    {
        return $this->isPublished;
    }

    public function publish(): self
    {
        $this->isPublished = true;
        $this->updateModifiedTimestamp();

        return $this;
    }

    public function unpublish(): self
    {
        $this->isPublished = false;
        $this->updateModifiedTimestamp();

        return $this;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;
        $this->updateModifiedTimestamp();

        return $this;
    }

    public function touch(): self
    {
        $this->updateModifiedTimestamp();

        return $this;
    }

    #[ORM\PreUpdate]
    private function updateModifiedTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
