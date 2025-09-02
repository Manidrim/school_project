<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250804001452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create articles table for blog functionality';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE articles_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE articles (
            id INT NOT NULL, 
            author_id INT NOT NULL, 
            last_modified_by_id INT DEFAULT NULL, 
            title VARCHAR(255) NOT NULL, 
            content TEXT NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            is_published BOOLEAN NOT NULL DEFAULT FALSE,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_BFDD3168F675F31B ON articles (author_id)');
        $this->addSql('CREATE INDEX IDX_BFDD3168D5E86FF ON articles (last_modified_by_id)');
        $this->addSql('CREATE INDEX IDX_BFDD31688B8E8428 ON articles (created_at)');
        $this->addSql('CREATE INDEX IDX_BFDD316843625D9F ON articles (is_published)');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_BFDD3168F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_BFDD3168D5E86FF FOREIGN KEY (last_modified_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN articles.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN articles.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE articles_id_seq CASCADE');
        $this->addSql('ALTER TABLE articles DROP CONSTRAINT FK_BFDD3168F675F31B');
        $this->addSql('ALTER TABLE articles DROP CONSTRAINT FK_BFDD3168D5E86FF');
        $this->addSql('DROP TABLE articles');
    }
}