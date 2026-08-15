<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731214446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add FSRS state columns to user_kanji/user_particle, snapshot columns to review_log, and seed FSRS state from existing SM-2 data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_kanji ADD COLUMN stability DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_kanji ADD COLUMN difficulty DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_kanji ADD COLUMN state INTEGER NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_kanji ADD COLUMN lapses INTEGER NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_kanji ADD COLUMN step INTEGER NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_kanji ADD COLUMN lastReviewedAt DATETIME DEFAULT NULL');

        $this->addSql('ALTER TABLE user_particle ADD COLUMN stability DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_particle ADD COLUMN difficulty DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_particle ADD COLUMN state INTEGER NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_particle ADD COLUMN lapses INTEGER NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_particle ADD COLUMN step INTEGER NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_particle ADD COLUMN lastReviewedAt DATETIME DEFAULT NULL');

        $this->addSql('ALTER TABLE review_log ADD COLUMN rating INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE review_log ADD COLUMN cardState INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE review_log ADD COLUMN stability DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE review_log ADD COLUMN difficulty DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE review_log ADD COLUMN scheduledDays INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE review_log ADD COLUMN elapsedDays INTEGER DEFAULT NULL');

        $this->warnIf(true, 'Converting existing SM-2 state to FSRS seed values for reviewed cards.');

        $this->addSql("UPDATE user_kanji SET
            stability = MAX(interval, 1),
            difficulty = MIN(10, MAX(1, 5.5 - (easeFactor - 2.5) * 2.0)),
            state = 2,
            lapses = 0,
            step = 0,
            lastReviewedAt = datetime(nextReviewAt, '-' || interval || ' days')
            WHERE repetitions > 0");

        $this->addSql("UPDATE user_particle SET
            stability = MAX(interval_value, 1),
            difficulty = MIN(10, MAX(1, 5.5 - (easeFactor - 2.5) * 2.0)),
            state = 2,
            lapses = 0,
            step = 0,
            lastReviewedAt = datetime(nextReviewAt, '-' || interval_value || ' days')
            WHERE repetitions > 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_kanji DROP COLUMN stability');
        $this->addSql('ALTER TABLE user_kanji DROP COLUMN difficulty');
        $this->addSql('ALTER TABLE user_kanji DROP COLUMN state');
        $this->addSql('ALTER TABLE user_kanji DROP COLUMN lapses');
        $this->addSql('ALTER TABLE user_kanji DROP COLUMN step');
        $this->addSql('ALTER TABLE user_kanji DROP COLUMN lastReviewedAt');

        $this->addSql('ALTER TABLE user_particle DROP COLUMN stability');
        $this->addSql('ALTER TABLE user_particle DROP COLUMN difficulty');
        $this->addSql('ALTER TABLE user_particle DROP COLUMN state');
        $this->addSql('ALTER TABLE user_particle DROP COLUMN lapses');
        $this->addSql('ALTER TABLE user_particle DROP COLUMN step');
        $this->addSql('ALTER TABLE user_particle DROP COLUMN lastReviewedAt');

        $this->addSql('ALTER TABLE review_log DROP COLUMN rating');
        $this->addSql('ALTER TABLE review_log DROP COLUMN cardState');
        $this->addSql('ALTER TABLE review_log DROP COLUMN stability');
        $this->addSql('ALTER TABLE review_log DROP COLUMN difficulty');
        $this->addSql('ALTER TABLE review_log DROP COLUMN scheduledDays');
        $this->addSql('ALTER TABLE review_log DROP COLUMN elapsedDays');
    }
}
