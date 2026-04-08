<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create refresh_token table for refresh token storage';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE refresh_token (id SERIAL NOT NULL, token VARCHAR(128) NOT NULL, user_id UUID NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_REFRESH_TOKEN_TOKEN ON refresh_token (token)');
        $this->addSql('CREATE INDEX IDX_REFRESH_TOKEN_USER_ID ON refresh_token (user_id)');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_REFRESH_TOKEN_USER_ID FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_token DROP CONSTRAINT FK_REFRESH_TOKEN_USER_ID');
        $this->addSql('DROP INDEX UNIQ_REFRESH_TOKEN_TOKEN');
        $this->addSql('DROP INDEX IDX_REFRESH_TOKEN_USER_ID');
        $this->addSql('DROP TABLE refresh_token');
    }
}
