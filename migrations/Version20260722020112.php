<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260722020112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__Kanji AS SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, easeFactor, interval, repetitions, nextReviewAt, createdAt, updatedAt FROM Kanji');
        $this->addSql('DROP TABLE Kanji');
        $this->addSql('CREATE TABLE Kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, character VARCHAR(10) NOT NULL, meanings CLOB NOT NULL, onyomi CLOB NOT NULL, kunyomi CLOB NOT NULL, jlptLevel VARCHAR(5) DEFAULT NULL, strokeCount INTEGER DEFAULT NULL, easeFactor DOUBLE PRECISION NOT NULL, interval INTEGER NOT NULL, repetitions INTEGER NOT NULL, nextReviewAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO Kanji (id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, easeFactor, interval, repetitions, nextReviewAt, createdAt, updatedAt) SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, easeFactor, interval, repetitions, nextReviewAt, createdAt, updatedAt FROM __temp__Kanji');
        $this->addSql('DROP TABLE __temp__Kanji');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_426F9DDC937AB034 ON Kanji (character)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__review_log AS SELECT id, quality, reviewedAt, kanji_id, reviewUser_id FROM review_log');
        $this->addSql('DROP TABLE review_log');
        $this->addSql('CREATE TABLE review_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quality INTEGER NOT NULL, reviewedAt DATETIME NOT NULL, kanji_id INTEGER NOT NULL, reviewUser_id INTEGER DEFAULT NULL, CONSTRAINT FK_6DECFF2DFB3081B8 FOREIGN KEY (kanji_id) REFERENCES Kanji (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6DECFF2D1B964A4A FOREIGN KEY (reviewUser_id) REFERENCES User (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO review_log (id, quality, reviewedAt, kanji_id, reviewUser_id) SELECT id, quality, reviewedAt, kanji_id, reviewUser_id FROM __temp__review_log');
        $this->addSql('DROP TABLE __temp__review_log');
        $this->addSql('CREATE INDEX IDX_8308640CFB3081B8 ON review_log (kanji_id)');
        $this->addSql('CREATE INDEX IDX_8308640C1B964A4A ON review_log (reviewUser_id)');
        $this->addSql('ALTER TABLE User ADD COLUMN timezone VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__kanji AS SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, easeFactor, interval, repetitions, nextReviewAt, createdAt, updatedAt FROM kanji');
        $this->addSql('DROP TABLE kanji');
        $this->addSql('CREATE TABLE kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, character VARCHAR(10) NOT NULL, meanings CLOB NOT NULL, onyomi CLOB NOT NULL, kunyomi CLOB NOT NULL, jlptLevel VARCHAR(5) DEFAULT NULL, strokeCount INTEGER DEFAULT NULL, easeFactor DOUBLE PRECISION NOT NULL, interval INTEGER NOT NULL, repetitions INTEGER NOT NULL, nextReviewAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO kanji (id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, easeFactor, interval, repetitions, nextReviewAt, createdAt, updatedAt) SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, easeFactor, interval, repetitions, nextReviewAt, createdAt, updatedAt FROM __temp__kanji');
        $this->addSql('DROP TABLE __temp__kanji');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_83AEB2D8937AB034 ON kanji (character)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, createdAt DATETIME NOT NULL, isVerified BOOLEAN NOT NULL, verificationToken VARCHAR(255) DEFAULT NULL, resetToken VARCHAR(255) DEFAULT NULL, resetTokenExpiresAt DATETIME DEFAULT NULL, totpSecret VARCHAR(255) DEFAULT NULL, totpEnabled BOOLEAN NOT NULL, avatarPath VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath) SELECT id, email, roles, password, name, createdAt, isVerified, verificationToken, resetToken, resetTokenExpiresAt, totpSecret, totpEnabled, avatarPath FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__review_log AS SELECT id, quality, reviewedAt, kanji_id, reviewUser_id FROM review_log');
        $this->addSql('DROP TABLE review_log');
        $this->addSql('CREATE TABLE review_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quality INTEGER NOT NULL, reviewedAt DATETIME NOT NULL, kanji_id INTEGER NOT NULL, reviewUser_id INTEGER DEFAULT NULL, CONSTRAINT FK_8308640CFB3081B8 FOREIGN KEY (kanji_id) REFERENCES kanji (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8308640C1B964A4A FOREIGN KEY (reviewUser_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO review_log (id, quality, reviewedAt, kanji_id, reviewUser_id) SELECT id, quality, reviewedAt, kanji_id, reviewUser_id FROM __temp__review_log');
        $this->addSql('DROP TABLE __temp__review_log');
        $this->addSql('CREATE INDEX IDX_6DECFF2D1B964A4A ON review_log (reviewUser_id)');
        $this->addSql('CREATE INDEX IDX_6DECFF2DFB3081B8 ON review_log (kanji_id)');
    }
}
