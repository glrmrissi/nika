<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260722231124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE name_history (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, name VARCHAR(100) NOT NULL, changed_at DATETIME NOT NULL)');
        $this->addSql('CREATE INDEX IDX_NAMES_USER ON name_history (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE name_history');
    }
}
