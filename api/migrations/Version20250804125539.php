<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250804125539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_bfdd316843625d9f');
        $this->addSql('DROP INDEX idx_bfdd31688b8e8428');
        $this->addSql('ALTER TABLE articles ALTER author_id DROP NOT NULL');
        $this->addSql('ALTER TABLE articles ALTER is_published DROP DEFAULT');
        $this->addSql('ALTER INDEX idx_bfdd3168d5e86ff RENAME TO IDX_BFDD3168F703974A');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE articles ALTER author_id SET NOT NULL');
        $this->addSql('ALTER TABLE articles ALTER is_published SET DEFAULT false');
        $this->addSql('CREATE INDEX idx_bfdd316843625d9f ON articles (is_published)');
        $this->addSql('CREATE INDEX idx_bfdd31688b8e8428 ON articles (created_at)');
        $this->addSql('ALTER INDEX idx_bfdd3168f703974a RENAME TO idx_bfdd3168d5e86ff');
    }
}
