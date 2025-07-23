<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250720210420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename email index to follow Doctrine naming convention';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX users_email_key RENAME TO UNIQ_1483A5E9E7927C74');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX UNIQ_1483A5E9E7927C74 RENAME TO users_email_key');
    }
}
