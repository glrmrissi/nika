<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722222452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add isComplete column to user_kanji';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_kanji ADD COLUMN isComplete BOOLEAN NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_kanji AS SELECT id, easeFactor, interval, repetitions, nextReviewAt, createdAt, user_id, kanji_id FROM user_kanji');
        $this->addSql('DROP TABLE user_kanji');
        $this->addSql('CREATE TABLE user_kanji (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, easeFactor DOUBLE PRECISION NOT NULL, interval INTEGER NOT NULL, repetitions INTEGER NOT NULL, nextReviewAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, user_id INTEGER NOT NULL, kanji_id INTEGER NOT NULL)');
        $this->addSql('INSERT INTO user_kanji (id, easeFactor, interval, repetitions, nextReviewAt, createdAt, user_id, kanji_id) SELECT id, easeFactor, interval, repetitions, nextReviewAt, createdAt, user_id, kanji_id FROM __temp__user_kanji');
        $this->addSql('DROP TABLE __temp__user_kanji');
        $this->addSql('CREATE INDEX IDX_USER_KANJI_USER ON user_kanji (user_id)');
        $this->addSql('CREATE INDEX IDX_USER_KANJI_KANJI ON user_kanji (kanji_id)');
    }
}
