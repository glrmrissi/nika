<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reviewedAt index for countReviewsToday query';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_review_log_reviewed_at ON review_log (reviewedAt)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_review_log_reviewed_at');
    }
}
