<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731221102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize migrated FSRS states and recover kanji lapse counts from legacy review logs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE user_kanji SET
            state = CASE
                WHEN repetitions >= 3 THEN 2
                WHEN repetitions > 0 THEN 1
                ELSE 0
            END,
            lapses = (
                SELECT COUNT(rl.id)
                FROM review_log rl
                WHERE rl.reviewUser_id = user_kanji.user_id
                AND rl.kanji_id = user_kanji.kanji_id
                AND rl.quality < 3
            )");

        $this->addSql("UPDATE user_kanji SET
            lastReviewedAt = datetime(nextReviewAt, '-' || interval || ' days')
            WHERE repetitions > 0 AND lastReviewedAt IS NULL");

        $this->addSql("UPDATE user_particle SET
            state = CASE
                WHEN repetitions >= 3 THEN 2
                WHEN repetitions > 0 THEN 1
                ELSE 0
            END
            WHERE state = 2 AND repetitions < 3");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE user_kanji SET state = CASE WHEN repetitions > 0 THEN 2 ELSE 0 END, lapses = 0');
        $this->addSql('UPDATE user_particle SET state = CASE WHEN repetitions > 0 THEN 2 ELSE 0 END');
    }
}
