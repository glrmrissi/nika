<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720232204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE User (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, createdAt DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON User (email)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ReviewLog AS SELECT id, quality, reviewedAt, kanji_id FROM ReviewLog');
        $this->addSql('DROP TABLE ReviewLog');
        $this->addSql('CREATE TABLE ReviewLog (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quality INTEGER NOT NULL, reviewedAt DATETIME NOT NULL, kanji_id INTEGER NOT NULL, reviewUser_id INTEGER DEFAULT NULL, CONSTRAINT FK_6DECFF2DFB3081B8 FOREIGN KEY (kanji_id) REFERENCES Kanji (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6DECFF2D1B964A4A FOREIGN KEY (reviewUser_id) REFERENCES User (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO ReviewLog (id, quality, reviewedAt, kanji_id) SELECT id, quality, reviewedAt, kanji_id FROM __temp__ReviewLog');
        $this->addSql('DROP TABLE __temp__ReviewLog');
        $this->addSql('CREATE INDEX IDX_6DECFF2DFB3081B8 ON ReviewLog (kanji_id)');
        $this->addSql('CREATE INDEX IDX_6DECFF2D1B964A4A ON ReviewLog (reviewUser_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE User');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ReviewLog AS SELECT id, quality, reviewedAt, kanji_id FROM ReviewLog');
        $this->addSql('DROP TABLE ReviewLog');
        $this->addSql('CREATE TABLE ReviewLog (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quality INTEGER NOT NULL, reviewedAt DATETIME NOT NULL, kanji_id INTEGER NOT NULL, CONSTRAINT FK_6DECFF2DFB3081B8 FOREIGN KEY (kanji_id) REFERENCES Kanji (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO ReviewLog (id, quality, reviewedAt, kanji_id) SELECT id, quality, reviewedAt, kanji_id FROM __temp__ReviewLog');
        $this->addSql('DROP TABLE __temp__ReviewLog');
        $this->addSql('CREATE INDEX IDX_6DECFF2DFB3081B8 ON ReviewLog (kanji_id)');
    }
}
