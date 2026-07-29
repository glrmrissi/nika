<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_particle table for SRS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_particle (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            user_id INTEGER NOT NULL,
            particle_id INTEGER NOT NULL,
            easeFactor DOUBLE PRECISION NOT NULL DEFAULT 2.5,
            interval_value INTEGER NOT NULL DEFAULT 0,
            repetitions INTEGER NOT NULL DEFAULT 0,
            nextReviewAt DATETIME NOT NULL,
            isComplete BOOLEAN NOT NULL DEFAULT 0,
            createdAt DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES user(id),
            FOREIGN KEY (particle_id) REFERENCES grammar_particle(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_particle');
    }
}
