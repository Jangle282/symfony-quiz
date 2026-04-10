<?php

namespace App\Controller;

use App\Attribute\RateLimited;
use App\Entity\Game;
use App\Service\QuestionService;
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
        \App\Service\GameService $gameService,
        QuestionService $questionService,
    ): JsonResponse {
        $user = $this->getUser();
        $data = $request->toArray();
        $difficultyName = $data['difficulty'] ?? 'medium';
        $gameName = $data['name'] ?? null;

        try {
            $result = $gameService->createGame($user, $difficultyName, $gameName);
        } catch (\App\Exception\GameException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
        }

        $game = $result['game'];
        $round = $result['round'];
        $category = $result['category'];
        $difficulty = $result['difficulty'];

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
    public function delete(Game $game, Request $request, \App\Service\GameService $gameService): Response
    {
        $this->denyAccessUnlessGranted('GAME_DELETE', $game);
        $gameService->deleteGame($game);
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/games/{id}', name: 'api_game_show', methods: ['GET'])]
    #[RateLimited('api_general')]
    public function show(Game $game, \App\Service\GameService $gameService): JsonResponse
    {
        $this->denyAccessUnlessGranted('GAME_VIEW', $game);
        $data = $gameService->getGameData($game);
        return $this->json($data);
    }

    #[Route('/api/games/{id}/complete', name: 'api_game_complete', methods: ['POST'])]
    #[RateLimited('api_general')]
    public function complete(Game $game, \App\Service\GameService $gameService): JsonResponse
    {
        $this->denyAccessUnlessGranted('GAME_VIEW', $game);
        try {
            $result = $gameService->completeGame($game);
        } catch (\App\Exception\GameException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
        return $this->json([
            'message' => 'Game completed.',
            'game_id' => $result['game_id'],
            'total_score' => $result['total_score'],
            'completed_at' => $result['completed_at'],
        ]);
    }

    #[Route('/api/games/{id}/results', name: 'api_game_results', methods: ['GET'])]
    #[RateLimited('api_general')]
    public function results(Game $game, \App\Service\GameService $gameService): JsonResponse
    {
        $this->denyAccessUnlessGranted('GAME_VIEW', $game);
        $data = $gameService->getResults($game);
        return $this->json($data);
    }
}
