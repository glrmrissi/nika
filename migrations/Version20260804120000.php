<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Discord webhook and last reminder timestamp to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD COLUMN discordWebhookUrl VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN discordReminderAt DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN discordWebhookUrl');
        $this->addSql('ALTER TABLE user DROP COLUMN discordReminderAt');
    }
}
