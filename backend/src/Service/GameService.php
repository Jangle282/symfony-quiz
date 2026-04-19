<?php

namespace App\Service;

use App\DTO\GameResultQuestionDTO;
use App\DTO\GameResultsDTO;
use App\Entity\Category;
use App\Entity\Difficulty;
use App\Entity\Game;
use App\Entity\Question;
use App\Entity\Round;
use App\Entity\UserGame;
use App\Entity\UserGameRole;
use App\Exception\GameException;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class GameService
{
    private EntityManagerInterface $entityManager;
    private GameRepository $gameRepository;

    public function __construct(EntityManagerInterface $entityManager, GameRepository $gameRepository)
    {
        $this->entityManager = $entityManager;
        $this->gameRepository = $gameRepository;
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

        $game = new Game();
        $game->setName($gameName);
        $game->setDifficulty($difficulty);
        $game->setCreatedBy($user);
        $game->setStartedAt(new \DateTimeImmutable());
        $this->entityManager->persist($game);

        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);
        $userGame->setRole(UserGameRole::Host);
        $this->entityManager->persist($userGame);
        $game->addUserGame($userGame);

        $round = new Round();
        $round->setGame($game);
        $round->setCategory($category);
        $round->setRoundNumber(1);
        $this->entityManager->persist($round);
        $game->addRound($round);

        $this->entityManager->flush();

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
        $game = $this->gameRepository->getGameWithRoundsAndQuestions($game->getId()) ?? $game;

        $rounds = [];
        $currentQuestion = null;

        foreach ($game->getRounds() as $round) {
            $questions = $round->getQuestions()->toArray();
            usort($questions, fn($a, $b) => strcmp($a->getId()->toString(), $b->getId()->toString()));

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
            'total_score' => $this->calculateTotalScore($game),
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

        $game->setCompletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return [
            'game_id' => $game->getId()->toString(),
            'total_score' => $totalScore,
            'completed_at' => $game->getCompletedAt()->format(DATE_ATOM),
        ];
    }

    public function getResults(Game $game): GameResultsDTO
    {
        $game = $this->gameRepository->getGameWithRoundsAndQuestions($game->getId()) ?? $game;

        $questions = [];
        foreach ($game->getRounds() as $round) {
            $orderedQuestions = $round->getQuestions()->toArray();
            usort($orderedQuestions, fn($a, $b) => strcmp($a->getId()->toString(), $b->getId()->toString()));

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

                $questions[] = new GameResultQuestionDTO(
                    questionId: $question->getId()->toString(),
                    questionText: $question->getQuestionText(),
                    correctAnswer: $correctAnswer,
                    selectedAnswer: $selectedAnswer,
                    isCorrect: $isCorrect,
                );
            }
        }

        return new GameResultsDTO(
            gameId: $game->getId()->toString(),
            totalScore: $this->calculateTotalScore($game),
            totalQuestions: count($questions),
            questions: $questions,
        );
    }

    public function calculateTotalScore(Game $game): int
    {
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

        return $totalScore;
    }
}
