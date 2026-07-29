<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260727200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop jlptLevel from grammar_particle';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE grammar_particle_temp (
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
            sortOrder INTEGER NOT NULL,
            createdAt DATETIME NOT NULL
        )');
        $this->addSql('INSERT INTO grammar_particle_temp SELECT id, particle, romaji, name, meaning, usageNote, exampleOne, exampleOneReading, exampleOneTranslation, exampleTwo, exampleTwoReading, exampleTwoTranslation, category, sortOrder, createdAt FROM grammar_particle');
        $this->addSql('DROP TABLE grammar_particle');
        $this->addSql('ALTER TABLE grammar_particle_temp RENAME TO grammar_particle');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE grammar_particle_old (
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
            jlptLevel VARCHAR(5),
            sortOrder INTEGER NOT NULL,
            createdAt DATETIME NOT NULL
        )');
        $this->addSql('INSERT INTO grammar_particle_old SELECT id, particle, romaji, name, meaning, usageNote, exampleOne, exampleOneReading, exampleOneTranslation, exampleTwo, exampleTwoReading, exampleTwoTranslation, category, NULL, sortOrder, createdAt FROM grammar_particle');
        $this->addSql('DROP TABLE grammar_particle');
        $this->addSql('ALTER TABLE grammar_particle_old RENAME TO grammar_particle');
    }
}
