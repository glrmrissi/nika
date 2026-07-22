<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260722001455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__User AS SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled FROM User');
        $this->addSql('DROP TABLE User');
        $this->addSql('CREATE TABLE User (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, createdAt DATETIME NOT NULL, isVerified BOOLEAN NOT NULL, verificationToken VARCHAR(255) DEFAULT NULL, resetToken VARCHAR(255) DEFAULT NULL, resetTokenExpiresAt DATETIME DEFAULT NULL, totpSecret VARCHAR(255) DEFAULT NULL, totpEnabled BOOLEAN NOT NULL, avatarPath VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO User (id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled) SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled FROM __temp__User');
        $this->addSql('DROP TABLE __temp__User');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON User (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__User AS SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled FROM User');
        $this->addSql('DROP TABLE User');
        $this->addSql('CREATE TABLE User (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, createdAt DATETIME NOT NULL, isVerified BOOLEAN DEFAULT 0 NOT NULL, verificationToken VARCHAR(255) DEFAULT NULL, resetToken VARCHAR(255) DEFAULT NULL, resetTokenExpiresAt DATETIME DEFAULT NULL, totpSecret VARCHAR(255) DEFAULT NULL, totpEnabled BOOLEAN DEFAULT 0 NOT NULL)');
        $this->addSql('INSERT INTO User (id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled) SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled FROM __temp__User');
        $this->addSql('DROP TABLE __temp__User');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON User (email)');
    }
}
