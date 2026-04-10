<?php

namespace App\Tests\Unit\Service;

use App\Entity\Game;
use App\Tests\Factory\UserFactory;
use App\Tests\Factory\CategoryFactory;
use App\Tests\Factory\DifficultyFactory;
use App\Service\GameService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class GameServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private GameService $gameService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->gameService = new GameService($this->entityManager);
        $this->seedReferenceData();
    }

    private function seedReferenceData(): void
    {
        CategoryFactory::createOne(['name' => 'General Knowledge']);
        DifficultyFactory::createOne(['name' => 'easy']);
        DifficultyFactory::createOne(['name' => 'medium']);
        DifficultyFactory::createOne(['name' => 'hard']);
    }

    public function testCreateGame(): void
    {
        $user = UserFactory::createOne()->_real();

        $result = $this->gameService->createGame($user, 'medium', 'Test Game');
        $game = $result['game'];
        $this->assertNotNull($game->getId());
        $this->assertEquals('Test Game', $game->getName());
        $this->assertEquals('medium', $game->getDifficulty()->getName());
        $this->assertEquals($user->getId(), $game->getCreatedBy()->getId());
    }

    public function testDeleteGame(): void
    {
        $user = UserFactory::createOne()->_real();
        $result = $this->gameService->createGame($user, 'medium', 'Delete Me');
        $game = $result['game'];
        $gameId = $game->getId();
        $this->gameService->deleteGame($game);
        $found = $this->entityManager->getRepository(Game::class)->find($gameId);
        $this->assertNull($found);
    }

    // ... More tests for completeGame, getGameData, getResults, error cases ...
}
