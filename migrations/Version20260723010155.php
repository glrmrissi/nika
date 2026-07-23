<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260723010155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__kanji AS SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt FROM kanji');
        $this->addSql('DROP TABLE kanji');
        $this->addSql('CREATE TABLE kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, character VARCHAR(10) NOT NULL, meanings CLOB NOT NULL, onyomi CLOB NOT NULL, kunyomi CLOB NOT NULL, jlptLevel VARCHAR(5) DEFAULT NULL, strokeCount INTEGER DEFAULT NULL, createdAt DATETIME NOT NULL)');
        $this->addSql('INSERT INTO kanji (id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt) SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt FROM __temp__kanji');
        $this->addSql('DROP TABLE __temp__kanji');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_426F9DDC937AB034 ON kanji (character)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__name_history AS SELECT id, user_id, name, changed_at FROM name_history');
        $this->addSql('DROP TABLE name_history');
        $this->addSql('CREATE TABLE name_history (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, name VARCHAR(100) NOT NULL, changed_at DATETIME NOT NULL, CONSTRAINT FK_2FD135F5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO name_history (id, user_id, name, changed_at) SELECT id, user_id, name, changed_at FROM __temp__name_history');
        $this->addSql('DROP TABLE __temp__name_history');
        $this->addSql('CREATE INDEX IDX_2FD135F5A76ED395 ON name_history (user_id)');
        $this->addSql('ALTER TABLE User ADD COLUMN kanjiClickAction VARCHAR(8) DEFAULT \'icon\' NOT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_kanji AS SELECT id, easeFactor, interval, repetitions, nextReviewAt, createdAt, user_id, kanji_id, isComplete FROM user_kanji');
        $this->addSql('DROP TABLE user_kanji');
        $this->addSql('CREATE TABLE user_kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, easeFactor DOUBLE PRECISION NOT NULL, interval INTEGER NOT NULL, repetitions INTEGER NOT NULL, nextReviewAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, user_id INTEGER NOT NULL, kanji_id INTEGER NOT NULL, isComplete BOOLEAN NOT NULL, CONSTRAINT FK_user_kanji_user FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_user_kanji_kanji FOREIGN KEY (kanji_id) REFERENCES kanji (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user_kanji (id, easeFactor, interval, repetitions, nextReviewAt, createdAt, user_id, kanji_id, isComplete) SELECT id, easeFactor, interval, repetitions, nextReviewAt, createdAt, user_id, kanji_id, isComplete FROM __temp__user_kanji');
        $this->addSql('DROP TABLE __temp__user_kanji');
        $this->addSql('CREATE INDEX IDX_A0AD6684A76ED395 ON user_kanji (user_id)');
        $this->addSql('CREATE INDEX IDX_A0AD6684FB3081B8 ON user_kanji (kanji_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath, timezone FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, createdAt DATETIME NOT NULL, isVerified BOOLEAN NOT NULL, verificationToken VARCHAR(255) DEFAULT NULL, resetToken VARCHAR(255) DEFAULT NULL, resetTokenExpiresAt DATETIME DEFAULT NULL, totpSecret VARCHAR(255) DEFAULT NULL, totpEnabled BOOLEAN NOT NULL, avatarPath VARCHAR(255) DEFAULT NULL, timezone VARCHAR(64) DEFAULT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath, timezone) SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath, timezone FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__kanji AS SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt FROM kanji');
        $this->addSql('DROP TABLE kanji');
        $this->addSql('CREATE TABLE kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, character VARCHAR(10) NOT NULL, meanings CLOB NOT NULL, onyomi CLOB NOT NULL, kunyomi CLOB NOT NULL, jlptLevel VARCHAR(5) DEFAULT NULL, strokeCount INTEGER DEFAULT NULL, createdAt DATETIME NOT NULL)');
        $this->addSql('INSERT INTO kanji (id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt) SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt FROM __temp__kanji');
        $this->addSql('DROP TABLE __temp__kanji');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_KANJI_CHARACTER ON kanji (character)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__name_history AS SELECT id, name, changed_at, user_id FROM name_history');
        $this->addSql('DROP TABLE name_history');
        $this->addSql('CREATE TABLE name_history (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, changed_at DATETIME NOT NULL, user_id INTEGER NOT NULL)');
        $this->addSql('INSERT INTO name_history (id, name, changed_at, user_id) SELECT id, name, changed_at, user_id FROM __temp__name_history');
        $this->addSql('DROP TABLE __temp__name_history');
        $this->addSql('CREATE INDEX IDX_NAMES_USER ON name_history (user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_kanji AS SELECT id, easeFactor, interval, repetitions, nextReviewAt, isComplete, createdAt, user_id, kanji_id FROM user_kanji');
        $this->addSql('DROP TABLE user_kanji');
        $this->addSql('CREATE TABLE user_kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, easeFactor DOUBLE PRECISION NOT NULL, interval INTEGER NOT NULL, repetitions INTEGER NOT NULL, nextReviewAt DATETIME NOT NULL, isComplete BOOLEAN DEFAULT 0 NOT NULL, createdAt DATETIME NOT NULL, user_id INTEGER NOT NULL, kanji_id INTEGER NOT NULL, CONSTRAINT FK_A0AD6684A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A0AD6684FB3081B8 FOREIGN KEY (kanji_id) REFERENCES kanji (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user_kanji (id, easeFactor, interval, repetitions, nextReviewAt, isComplete, createdAt, user_id, kanji_id) SELECT id, easeFactor, interval, repetitions, nextReviewAt, isComplete, createdAt, user_id, kanji_id FROM __temp__user_kanji');
        $this->addSql('DROP TABLE __temp__user_kanji');
        $this->addSql('CREATE INDEX IDX_USER_KANJI_KANJI ON user_kanji (kanji_id)');
        $this->addSql('CREATE INDEX IDX_USER_KANJI_USER ON user_kanji (user_id)');
    }
}
