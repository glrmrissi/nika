<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260721221836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE User ADD COLUMN isVerified BOOLEAN NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE User ADD COLUMN verificationToken VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE User ADD COLUMN resetToken VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE User ADD COLUMN resetTokenExpiresAt DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE User ADD COLUMN totpSecret VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE User ADD COLUMN totpEnabled BOOLEAN NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE User_backup AS SELECT id, email, roles, password, name, createdAt FROM User');
        $this->addSql('DROP TABLE User');
        $this->addSql('CREATE TABLE User (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, createdAt DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON User (email)');
        $this->addSql('INSERT INTO User (id, email, roles, password, name, createdAt) SELECT id, email, roles, password, name, createdAt FROM User_backup');
        $this->addSql('DROP TABLE User_backup');
    }
}
