<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Category;
use App\Entity\Difficulty;
use App\Entity\Game;
use App\Entity\Round;
use App\Entity\UserGame;
use App\Service\QuestionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    #[Route('/api/games', name: 'api_game_create', methods: ['POST'])]
    #[RateLimited('api_general')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        QuestionService $questionService,
    ): JsonResponse {
        $user = $this->getUser();

        $data = $request->toArray();
        $difficultyName = $data['difficulty'] ?? 'medium';
        $gameName = $data['name'] ?? null;

        $difficulty = $entityManager->getRepository(Difficulty::class)->findOneBy(['name' => $difficultyName]);
        if (!$difficulty) {
            return $this->json(['error' => 'Invalid difficulty. Valid values: easy, medium, hard.'], Response::HTTP_BAD_REQUEST);
        }

        $category = $entityManager->getRepository(Category::class)->findOneBy(['name' => 'General Knowledge']);
        if (!$category) {
            return $this->json(['error' => 'Category "General Knowledge" not found. Please seed the database.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $game = new Game();
        $game->setName($gameName);
        $game->setDifficulty($difficulty);
        $game->setCreatedBy($user);
        $game->setTotalScore(0);
        $game->setStartedAt(new \DateTimeImmutable());
        $entityManager->persist($game);

        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);
        $userGame->setRole('host');
        $entityManager->persist($userGame);
        $game->addUserGame($userGame);

        $round = new Round();
        $round->setGame($game);
        $round->setCategory($category);
        $round->setRoundNumber(1);
        $entityManager->persist($round);
        $game->addRound($round);

        $entityManager->flush();

        $questionService->createQuestionsAndAnswers([$round], $difficulty, 5);

        $firstQuestion = $round->getQuestions()->first();

        $questionData = null;
        if ($firstQuestion) {
            $answers = [];
            foreach ($firstQuestion->getAnswers() as $answer) {
                $answers[] = [
                    'id' => $answer->getId()->toString(),
                    'answer_text' => $answer->getAnswerText(),
                ];
            }

            $questionData = [
                'id' => $firstQuestion->getId()->toString(),
                'question_text' => $firstQuestion->getQuestionText(),
                'answers' => $answers,
            ];
        }

        return $this->json([
            'id' => $game->getId()->toString(),
            'name' => $game->getName(),
            'difficulty' => $difficulty->getName(),
            'round' => [
                'id' => $round->getId()->toString(),
                'round_number' => $round->getRoundNumber(),
                'category' => $category->getName(),
            ],
            'first_question' => $questionData,
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/games/{id}', name: 'api_game_delete', methods: ['DELETE'])]
    #[RateLimited('api_general')]
    public function delete(Game $game, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('GAME_DELETE', $game);

        $entityManager->remove($game);
        $entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
