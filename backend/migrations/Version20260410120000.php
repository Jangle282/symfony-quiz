<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop total_score column from quiz_games table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_games DROP COLUMN total_score');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_games ADD COLUMN total_score INT NOT NULL DEFAULT 0');
    }
}
