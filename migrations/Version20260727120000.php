<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260727120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create grammar_particle table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE grammar_particle (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            particle VARCHAR(10) NOT NULL,
            romaji VARCHAR(20) NOT NULL,
            name VARCHAR(100) NOT NULL,
            meaning CLOB NOT NULL,
            usageNote CLOB NOT NULL,
            exampleOne CLOB NOT NULL,
            exampleOneReading CLOB NOT NULL,
            exampleOneTranslation CLOB NOT NULL,
            exampleTwo CLOB NOT NULL,
            exampleTwoReading CLOB NOT NULL,
            exampleTwoTranslation CLOB NOT NULL,
            category VARCHAR(50) NOT NULL,
            jlptLevel VARCHAR(5) NOT NULL,
            sortOrder INTEGER NOT NULL,
            createdAt DATETIME NOT NULL
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE grammar_particle');
    }
}
