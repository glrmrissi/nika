<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260729000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create activity and activity_type tables, seed activity types';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE activity_type (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(50) NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ACTIVITY_TYPE_SLUG ON activity_type (slug)');

        $this->addSql('CREATE TABLE activity (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            user_id INTEGER NOT NULL,
            type_id INTEGER NOT NULL,
            metadata TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES user(id),
            FOREIGN KEY (type_id) REFERENCES activity_type(id)
        )');
        $this->addSql('CREATE INDEX IDX_ACTIVITY_USER ON activity (user_id)');
        $this->addSql('CREATE INDEX IDX_ACTIVITY_TYPE ON activity (type_id)');
        $this->addSql('CREATE INDEX IDX_ACTIVITY_CREATED_AT ON activity (created_at)');

        $this->addSql("INSERT INTO activity_type (id, name, slug) VALUES (1, 'Kanji Review', 'kanji_review')");
        $this->addSql("INSERT INTO activity_type (id, name, slug) VALUES (2, 'Particle Review', 'particle_review')");
        $this->addSql("INSERT INTO activity_type (id, name, slug) VALUES (3, 'Particle Quiz', 'particle_quiz')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE activity');
        $this->addSql('DROP TABLE activity_type');
    }
}
