<?php

namespace App\Tests\Unit\Service;

use App\Entity\Game;
use App\Service\GameService;
use App\Tests\Factory\CategoryFactory;
use App\Tests\Factory\DifficultyFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class GameServiceTest extends KernelTestCase
{
    use Factories;

    private GameService $gameService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->gameService = static::getContainer()->get(GameService::class);
    }

    public function testCreateGame(): void
    {
        DifficultyFactory::createOne(['name' => 'medium']);
        CategoryFactory::createOne(['name' => 'General Knowledge']);
        $user = UserFactory::createOne();

        $result = $this->gameService->createGame($user->_real(), 'medium', 'Test Game');
        $game = $result['game'];
        $this->assertNotNull($game->getId());
        $this->assertEquals('Test Game', $game->getName());
        $this->assertEquals('medium', $game->getDifficulty()->getName());
        $this->assertEquals($user->getId(), $game->getCreatedBy()->getId());
    }

    public function testDeleteGame(): void
    {
        DifficultyFactory::createOne(['name' => 'medium']);
        CategoryFactory::createOne(['name' => 'General Knowledge']);
        $user = UserFactory::createOne();

        $result = $this->gameService->createGame($user->_real(), 'medium', 'Delete Me');
        $game = $result['game'];
        $gameId = $game->getId();
        $this->gameService->deleteGame($game);
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $found = $em->getRepository(Game::class)->find($gameId);
        $this->assertNull($found);
    }
}
