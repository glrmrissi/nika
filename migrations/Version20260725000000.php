<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes for performance optimization';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_user_kanji_user_kanji ON user_kanji (user_id, kanji_id)');
        $this->addSql('CREATE INDEX idx_user_kanji_user_next_review ON user_kanji (user_id, nextReviewAt)');
        $this->addSql('CREATE INDEX idx_review_log_user_reviewed ON review_log (reviewUser_id, reviewedAt)');
        $this->addSql('CREATE INDEX idx_kanji_jlpt_level ON kanji (jlptLevel)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_user_kanji_user_kanji');
        $this->addSql('DROP INDEX idx_user_kanji_user_next_review');
        $this->addSql('DROP INDEX idx_review_log_user_reviewed');
        $this->addSql('DROP INDEX idx_kanji_jlpt_level');
    }
}
