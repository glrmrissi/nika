<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add length constraints to user fields via CHECK';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__User AS SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath, timezone, kanjiClickAction, readme FROM User');
        $this->addSql('DROP TABLE User');
        $this->addSql("CREATE TABLE User (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, createdAt DATETIME NOT NULL, isVerified BOOLEAN NOT NULL, verificationToken VARCHAR(255) DEFAULT NULL, resetToken VARCHAR(255) DEFAULT NULL, resetTokenExpiresAt DATETIME DEFAULT NULL, totpSecret VARCHAR(255) DEFAULT NULL, totpEnabled BOOLEAN NOT NULL, avatarPath VARCHAR(255) DEFAULT NULL, timezone VARCHAR(64) DEFAULT NULL, kanjiClickAction VARCHAR(8) DEFAULT 'icon' NOT NULL, readme CLOB DEFAULT NULL, CHECK(length(name) <= 100), CHECK(length(email) <= 180), CHECK(readme IS NULL OR length(readme) <= 5000))");
        $this->addSql('INSERT INTO User (id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath, timezone, kanjiClickAction, readme) SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath, timezone, kanjiClickAction, readme FROM __temp__User');
        $this->addSql('DROP TABLE __temp__User');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON User (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__User AS SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath, timezone, kanjiClickAction, readme FROM User');
        $this->addSql('DROP TABLE User');
        $this->addSql("CREATE TABLE User (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, createdAt DATETIME NOT NULL, isVerified BOOLEAN NOT NULL, verificationToken VARCHAR(255) DEFAULT NULL, resetToken VARCHAR(255) DEFAULT NULL, resetTokenExpiresAt DATETIME DEFAULT NULL, totpSecret VARCHAR(255) DEFAULT NULL, totpEnabled BOOLEAN NOT NULL, avatarPath VARCHAR(255) DEFAULT NULL, timezone VARCHAR(64) DEFAULT NULL, kanjiClickAction VARCHAR(8) DEFAULT 'icon' NOT NULL, readme CLOB DEFAULT NULL)");
        $this->addSql('INSERT INTO User (id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath, timezone, kanjiClickAction, readme) SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath, timezone, kanjiClickAction, readme FROM __temp__User');
        $this->addSql('DROP TABLE __temp__User');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON User (email)');
    }
}
