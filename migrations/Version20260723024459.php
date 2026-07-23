<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723024459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add readme field to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, name, created_at, is_verified, verification_token, reset_token, reset_token_expires_at, totp_secret, totp_enabled, avatar_path, timezone, kanji_click_action FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, is_verified BOOLEAN NOT NULL, verification_token VARCHAR(255) DEFAULT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_token_expires_at DATETIME DEFAULT NULL, totp_secret VARCHAR(255) DEFAULT NULL, totp_enabled BOOLEAN NOT NULL, avatar_path VARCHAR(255) DEFAULT NULL, timezone VARCHAR(64) DEFAULT NULL, kanji_click_action VARCHAR(8) DEFAULT \'icon\' NOT NULL, readme CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, name, created_at, is_verified, verification_token, reset_token, reset_token_expires_at, totp_secret, totp_enabled, avatar_path, timezone, kanji_click_action) SELECT id, email, roles, password, name, created_at, is_verified, verification_token, reset_token, reset_token_expires_at, totp_secret, totp_enabled, avatar_path, timezone, kanji_click_action FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, name, created_at, is_verified, verification_token, reset_token, reset_token_expires_at, totp_secret, totp_enabled, avatar_path, timezone, kanji_click_action FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, is_verified BOOLEAN NOT NULL, verification_token VARCHAR(255) DEFAULT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_token_expires_at DATETIME DEFAULT NULL, totp_secret VARCHAR(255) DEFAULT NULL, totp_enabled BOOLEAN NOT NULL, avatar_path VARCHAR(255) DEFAULT NULL, timezone VARCHAR(64) DEFAULT NULL, kanji_click_action VARCHAR(8) DEFAULT \'icon\' NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, name, created_at, is_verified, verification_token, reset_token, reset_token_expires_at, totp_secret, totp_enabled, avatar_path, timezone, kanji_click_action) SELECT id, email, roles, password, name, created_at, is_verified, verification_token, reset_token, reset_token_expires_at, totp_secret, totp_enabled, avatar_path, timezone, kanji_click_action FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }
}
