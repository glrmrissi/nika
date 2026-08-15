<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260815023301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, metadata CLOB DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, type_id INTEGER NOT NULL, CONSTRAINT FK_AC74095AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AC74095AC54C8C93 FOREIGN KEY (type_id) REFERENCES activity_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_AC74095AA76ED395 ON activity (user_id)');
        $this->addSql('CREATE INDEX IDX_AC74095AC54C8C93 ON activity (type_id)');
        $this->addSql('CREATE TABLE activity_type (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(50) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8F1A8CBB989D9B62 ON activity_type (slug)');
        $this->addSql('CREATE TABLE grammar_particle (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, particle VARCHAR(10) NOT NULL, romaji VARCHAR(20) NOT NULL, name VARCHAR(100) NOT NULL, meaning CLOB NOT NULL, usageNote CLOB NOT NULL, exampleOne CLOB NOT NULL, exampleOneReading CLOB NOT NULL, exampleOneTranslation CLOB NOT NULL, exampleTwo CLOB NOT NULL, exampleTwoReading CLOB NOT NULL, exampleTwoTranslation CLOB NOT NULL, category VARCHAR(50) NOT NULL, sortOrder INTEGER NOT NULL, createdAt DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, character VARCHAR(10) NOT NULL, meanings CLOB NOT NULL, onyomi CLOB NOT NULL, kunyomi CLOB NOT NULL, jlptLevel VARCHAR(5) DEFAULT NULL, strokeCount INTEGER DEFAULT NULL, createdAt DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_426F9DDC937AB034 ON kanji (character)');
        $this->addSql('CREATE TABLE name_history (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, changed_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_2FD135F5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2FD135F5A76ED395 ON name_history (user_id)');
        $this->addSql('CREATE TABLE review_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quality INTEGER NOT NULL, rating INTEGER DEFAULT NULL, cardState INTEGER DEFAULT NULL, stability DOUBLE PRECISION DEFAULT NULL, difficulty DOUBLE PRECISION DEFAULT NULL, scheduledDays INTEGER DEFAULT NULL, elapsedDays INTEGER DEFAULT NULL, reviewedAt DATETIME NOT NULL, kanji_id INTEGER NOT NULL, reviewUser_id INTEGER DEFAULT NULL, CONSTRAINT FK_8308640CFB3081B8 FOREIGN KEY (kanji_id) REFERENCES kanji (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8308640C1B964A4A FOREIGN KEY (reviewUser_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8308640CFB3081B8 ON review_log (kanji_id)');
        $this->addSql('CREATE INDEX IDX_8308640C1B964A4A ON review_log (reviewUser_id)');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, createdAt DATETIME NOT NULL, isVerified BOOLEAN NOT NULL, verificationToken VARCHAR(255) DEFAULT NULL, resetToken VARCHAR(255) DEFAULT NULL, resetTokenExpiresAt DATETIME DEFAULT NULL, totpSecret VARCHAR(255) DEFAULT NULL, totpEnabled BOOLEAN NOT NULL, avatarPath VARCHAR(255) DEFAULT NULL, timezone VARCHAR(64) DEFAULT NULL, kanjiClickAction VARCHAR(8) DEFAULT \'icon\' NOT NULL, readme CLOB DEFAULT NULL, discordWebhookUrl VARCHAR(500) DEFAULT NULL, discordReminderAt DATETIME DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE TABLE user_kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, easeFactor DOUBLE PRECISION NOT NULL, interval INTEGER NOT NULL, repetitions INTEGER NOT NULL, nextReviewAt DATETIME NOT NULL, isComplete BOOLEAN NOT NULL, stability DOUBLE PRECISION NOT NULL, difficulty DOUBLE PRECISION NOT NULL, state INTEGER NOT NULL, lapses INTEGER NOT NULL, step INTEGER NOT NULL, lastReviewedAt DATETIME DEFAULT NULL, createdAt DATETIME NOT NULL, user_id INTEGER NOT NULL, kanji_id INTEGER NOT NULL, CONSTRAINT FK_A0AD6684A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A0AD6684FB3081B8 FOREIGN KEY (kanji_id) REFERENCES kanji (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A0AD6684A76ED395 ON user_kanji (user_id)');
        $this->addSql('CREATE INDEX IDX_A0AD6684FB3081B8 ON user_kanji (kanji_id)');
        $this->addSql('CREATE TABLE user_particle (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, easeFactor DOUBLE PRECISION NOT NULL, interval_value INTEGER NOT NULL, repetitions INTEGER NOT NULL, nextReviewAt DATETIME NOT NULL, isComplete BOOLEAN NOT NULL, stability DOUBLE PRECISION NOT NULL, difficulty DOUBLE PRECISION NOT NULL, state INTEGER NOT NULL, lapses INTEGER NOT NULL, step INTEGER NOT NULL, lastReviewedAt DATETIME DEFAULT NULL, createdAt DATETIME NOT NULL, user_id INTEGER NOT NULL, particle_id INTEGER NOT NULL, CONSTRAINT FK_B6A97BE1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B6A97BE14BB0DF5 FOREIGN KEY (particle_id) REFERENCES grammar_particle (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B6A97BE1A76ED395 ON user_particle (user_id)');
        $this->addSql('CREATE INDEX IDX_B6A97BE14BB0DF5 ON user_particle (particle_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE activity');
        $this->addSql('DROP TABLE activity_type');
        $this->addSql('DROP TABLE grammar_particle');
        $this->addSql('DROP TABLE kanji');
        $this->addSql('DROP TABLE name_history');
        $this->addSql('DROP TABLE review_log');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_kanji');
        $this->addSql('DROP TABLE user_particle');
    }
}
