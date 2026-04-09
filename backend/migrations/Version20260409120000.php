<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all tables for the quiz application';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, username VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USERS_USERNAME ON users (username)');

        $this->addSql('CREATE TABLE quiz_difficulty (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');

        $this->addSql('CREATE TABLE quiz_category (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');

        $this->addSql('CREATE TABLE quiz_games (id UUID NOT NULL, name VARCHAR(255) DEFAULT NULL, difficulty_id UUID NOT NULL, created_by_id UUID NOT NULL, total_score INT NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_QUIZ_GAMES_DIFFICULTY ON quiz_games (difficulty_id)');
        $this->addSql('CREATE INDEX IDX_QUIZ_GAMES_CREATED_BY ON quiz_games (created_by_id)');

        $this->addSql('CREATE TABLE user_game (id UUID NOT NULL, user_id UUID NOT NULL, game_id UUID NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, role VARCHAR(20) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_USER_GAME_USER ON user_game (user_id)');
        $this->addSql('CREATE INDEX IDX_USER_GAME_GAME ON user_game (game_id)');

        $this->addSql('CREATE TABLE quiz_rounds (id UUID NOT NULL, game_id UUID NOT NULL, category_id UUID NOT NULL, round_number INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_QUIZ_ROUNDS_GAME ON quiz_rounds (game_id)');
        $this->addSql('CREATE INDEX IDX_QUIZ_ROUNDS_CATEGORY ON quiz_rounds (category_id)');

        $this->addSql('CREATE TABLE quiz_questions (id UUID NOT NULL, round_id UUID NOT NULL, question_text TEXT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_QUIZ_QUESTIONS_ROUND ON quiz_questions (round_id)');

        $this->addSql('CREATE TABLE quiz_answers (id UUID NOT NULL, question_id UUID NOT NULL, user_selected BOOLEAN NOT NULL, is_correct BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_QUIZ_ANSWERS_QUESTION ON quiz_answers (question_id)');

        $this->addSql('CREATE TABLE refresh_token (id SERIAL NOT NULL, token VARCHAR(128) NOT NULL, user_id UUID NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_REFRESH_TOKEN_TOKEN ON refresh_token (token)');
        $this->addSql('CREATE INDEX IDX_REFRESH_TOKEN_USER ON refresh_token (user_id)');

        // Foreign keys
        $this->addSql('ALTER TABLE quiz_games ADD CONSTRAINT FK_QUIZ_GAMES_DIFFICULTY FOREIGN KEY (difficulty_id) REFERENCES quiz_difficulty (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE quiz_games ADD CONSTRAINT FK_QUIZ_GAMES_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE user_game ADD CONSTRAINT FK_USER_GAME_USER FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE user_game ADD CONSTRAINT FK_USER_GAME_GAME FOREIGN KEY (game_id) REFERENCES quiz_games (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE quiz_rounds ADD CONSTRAINT FK_QUIZ_ROUNDS_GAME FOREIGN KEY (game_id) REFERENCES quiz_games (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE quiz_rounds ADD CONSTRAINT FK_QUIZ_ROUNDS_CATEGORY FOREIGN KEY (category_id) REFERENCES quiz_category (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE quiz_questions ADD CONSTRAINT FK_QUIZ_QUESTIONS_ROUND FOREIGN KEY (round_id) REFERENCES quiz_rounds (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE quiz_answers ADD CONSTRAINT FK_QUIZ_ANSWERS_QUESTION FOREIGN KEY (question_id) REFERENCES quiz_questions (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_REFRESH_TOKEN_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_answers DROP CONSTRAINT FK_QUIZ_ANSWERS_QUESTION');
        $this->addSql('ALTER TABLE quiz_questions DROP CONSTRAINT FK_QUIZ_QUESTIONS_ROUND');
        $this->addSql('ALTER TABLE quiz_rounds DROP CONSTRAINT FK_QUIZ_ROUNDS_GAME');
        $this->addSql('ALTER TABLE quiz_rounds DROP CONSTRAINT FK_QUIZ_ROUNDS_CATEGORY');
        $this->addSql('ALTER TABLE user_game DROP CONSTRAINT FK_USER_GAME_USER');
        $this->addSql('ALTER TABLE user_game DROP CONSTRAINT FK_USER_GAME_GAME');
        $this->addSql('ALTER TABLE quiz_games DROP CONSTRAINT FK_QUIZ_GAMES_DIFFICULTY');
        $this->addSql('ALTER TABLE quiz_games DROP CONSTRAINT FK_QUIZ_GAMES_CREATED_BY');
        $this->addSql('ALTER TABLE refresh_token DROP CONSTRAINT FK_REFRESH_TOKEN_USER');
        $this->addSql('DROP TABLE quiz_answers');
        $this->addSql('DROP TABLE quiz_questions');
        $this->addSql('DROP TABLE quiz_rounds');
        $this->addSql('DROP TABLE user_game');
        $this->addSql('DROP TABLE quiz_games');
        $this->addSql('DROP TABLE quiz_difficulty');
        $this->addSql('DROP TABLE quiz_category');
        $this->addSql('DROP TABLE refresh_token');
        $this->addSql('DROP TABLE users');
    }
}
