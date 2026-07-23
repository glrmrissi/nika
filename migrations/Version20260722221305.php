<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722221305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move SRS fields from kanji to user_kanji table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, easeFactor DOUBLE PRECISION NOT NULL, interval INTEGER NOT NULL, repetitions INTEGER NOT NULL, nextReviewAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, user_id INTEGER NOT NULL, kanji_id INTEGER NOT NULL, CONSTRAINT FK_user_kanji_user FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_user_kanji_kanji FOREIGN KEY (kanji_id) REFERENCES kanji (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_USER_KANJI_USER ON user_kanji (user_id)');
        $this->addSql('CREATE INDEX IDX_USER_KANJI_KANJI ON user_kanji (kanji_id)');

        $this->addSql('CREATE TEMPORARY TABLE __temp__kanji AS SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt FROM kanji');
        $this->addSql('DROP TABLE kanji');
        $this->addSql('CREATE TABLE kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, character VARCHAR(10) NOT NULL, meanings CLOB NOT NULL, onyomi CLOB NOT NULL, kunyomi CLOB NOT NULL, jlptLevel VARCHAR(5) DEFAULT NULL, strokeCount INTEGER DEFAULT NULL, createdAt DATETIME NOT NULL)');
        $this->addSql('INSERT INTO kanji (id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt) SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt FROM __temp__kanji');
        $this->addSql('DROP TABLE __temp__kanji');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_KANJI_CHARACTER ON kanji (character)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_kanji');

        $this->addSql('CREATE TEMPORARY TABLE __temp__kanji AS SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt FROM kanji');
        $this->addSql('DROP TABLE kanji');
        $this->addSql('CREATE TABLE kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, character VARCHAR(10) NOT NULL, meanings CLOB NOT NULL, onyomi CLOB NOT NULL, kunyomi CLOB NOT NULL, jlptLevel VARCHAR(5) DEFAULT NULL, strokeCount INTEGER DEFAULT NULL, easeFactor DOUBLE PRECISION NOT NULL DEFAULT 2.5, interval INTEGER NOT NULL DEFAULT 0, repetitions INTEGER NOT NULL DEFAULT 0, nextReviewAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO kanji (id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt) SELECT id, character, meanings, onyomi, kunyomi, jlptLevel, strokeCount, createdAt FROM __temp__kanji');
        $this->addSql('DROP TABLE __temp__kanji');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_KANJI_CHARACTER ON kanji (character)');
    }
}
