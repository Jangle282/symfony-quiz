<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Difficulty;
use App\Entity\Game;
use App\Entity\Question;
use App\Entity\Round;
use App\Entity\UserGame;
use App\Exception\GameException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class GameService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function createGame(UserInterface $user, string $difficultyName = 'medium', ?string $gameName = null): array
    {
        $difficulty = $this->entityManager->getRepository(Difficulty::class)->findOneBy(['name' => $difficultyName]);
        if (!$difficulty) {
            throw GameException::invalidDifficulty();
        }

        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => 'General Knowledge']);
        if (!$category) {
            throw GameException::categoryNotFound();
        }

        $game = $this->entityManager->getRepository(Game::class)->create($user, $difficulty, $category, $gameName);
        $userGame = $this->entityManager->getRepository(UserGame::class)->create($user, $game, 'host');
        $round = $this->entityManager->getRepository(Round::class)->create($game, $category);
        
        return [
            'game' => $game,
            'round' => $round,
            'category' => $category,
            'difficulty' => $difficulty,
            'userGame' => $userGame,
        ];
    }

    public function deleteGame(Game $game): void
    {
        $this->entityManager->remove($game);
        $this->entityManager->flush();
    }

    public function getGameData(Game $game): array
    {
        $rounds = [];
        $currentQuestion = null;

        foreach ($game->getRounds() as $round) {
            $questions = $this->entityManager->getRepository(Question::class)
                ->findBy(['round' => $round], ['id' => 'ASC']);

            $totalQuestions = count($questions);
            $answeredQuestions = 0;

            foreach ($questions as $question) {
                $answered = false;
                foreach ($question->getAnswers() as $answer) {
                    if ($answer->isUserSelected()) {
                        $answered = true;
                        break;
                    }
                }
                if ($answered) {
                    $answeredQuestions++;
                } elseif ($currentQuestion === null) {
                    $currentQuestion = $question;
                }
            }

            $rounds[] = [
                'id' => $round->getId()->toString(),
                'round_number' => $round->getRoundNumber(),
                'category' => $round->getCategory()->getName(),
                'total_questions' => $totalQuestions,
                'answered_questions' => $answeredQuestions,
            ];
        }

        $currentQuestionData = null;
        if ($currentQuestion) {
            $answers = [];
            foreach ($currentQuestion->getAnswers() as $answer) {
                $answers[] = [
                    'id' => $answer->getId()->toString(),
                    'answer_text' => $answer->getAnswerText(),
                    'user_selected' => $answer->isUserSelected(),
                ];
            }
            $currentQuestionData = [
                'id' => $currentQuestion->getId()->toString(),
                'question_text' => $currentQuestion->getQuestionText(),
                'answers' => $answers,
            ];
        }

        return [
            'id' => $game->getId()->toString(),
            'name' => $game->getName(),
            'difficulty' => $game->getDifficulty()->getName(),
            'total_score' => $game->getTotalScore(),
            'started_at' => $game->getStartedAt()->format(DATE_ATOM),
            'completed_at' => $game->getCompletedAt()?->format(DATE_ATOM),
            'rounds' => $rounds,
            'current_question' => $currentQuestionData,
        ];
    }

    public function completeGame(Game $game): array
    {
        if ($game->getCompletedAt() !== null) {
            throw GameException::alreadyCompleted();
        }

        $totalScore = 0;
        foreach ($game->getRounds() as $round) {
            foreach ($round->getQuestions() as $question) {
                foreach ($question->getAnswers() as $answer) {
                    if ($answer->isUserSelected() && $answer->isCorrect()) {
                        $totalScore++;
                    }
                }
            }
        }

        $game->setTotalScore($totalScore);
        $game->setCompletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return [
            'game_id' => $game->getId()->toString(),
            'total_score' => $totalScore,
            'completed_at' => $game->getCompletedAt()->format(DATE_ATOM),
        ];
    }

    public function getResults(Game $game): array
    {
        $questions = [];
        foreach ($game->getRounds() as $round) {
            $orderedQuestions = $this->entityManager->getRepository(Question::class)
                ->findBy(['round' => $round], ['id' => 'ASC']);

            foreach ($orderedQuestions as $question) {
                $correctAnswer = null;
                $selectedAnswer = null;

                foreach ($question->getAnswers() as $answer) {
                    if ($answer->isCorrect()) {
                        $correctAnswer = $answer->getAnswerText();
                    }
                    if ($answer->isUserSelected()) {
                        $selectedAnswer = $answer->getAnswerText();
                    }
                }

                $isCorrect = $correctAnswer !== null && $correctAnswer === $selectedAnswer;

                $questions[] = [
                    'question_id' => $question->getId()->toString(),
                    'question_text' => $question->getQuestionText(),
                    'correct_answer' => $correctAnswer,
                    'selected_answer' => $selectedAnswer,
                    'is_correct' => $isCorrect,
                ];
            }
        }

        return [
            'game_id' => $game->getId()->toString(),
            'total_score' => $game->getTotalScore(),
            'total_questions' => count($questions),
            'questions' => $questions,
        ];
    }
}
