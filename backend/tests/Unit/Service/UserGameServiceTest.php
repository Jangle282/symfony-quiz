<?php

namespace App\Tests\Unit\Service;

use App\Exception\ConflictException;
use App\Service\UserGameService;
use App\Tests\Factory\DifficultyFactory;
use App\Tests\Factory\GameFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Factory\UserGameFactory;
use App\Entity\UserGameRole;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class UserGameServiceTest extends KernelTestCase
{
    use Factories;

    private UserGameService $userGameService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->userGameService = static::getContainer()->get(UserGameService::class);
    }

    public function testJoinGameCreatesUserGame(): void
    {
        $owner = UserFactory::createOne();
        $joiner = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $game = GameFactory::createOne(['createdBy' => $owner, 'difficulty' => $difficulty]);
        UserGameFactory::createOne(['user' => $owner, 'game' => $game, 'role' => UserGameRole::Host]);

        $userGame = $this->userGameService->joinGame($joiner->_real(), $game->_real());

        $this->assertSame(UserGameRole::Participant, $userGame->getRole());
        $this->assertSame($joiner->getId()->toString(), $userGame->getUser()->getId()->toString());
    }

    public function testJoinGameThrowsOnDuplicate(): void
    {
        $user = UserFactory::createOne();
        $difficulty = DifficultyFactory::createOne(['name' => 'medium']);
        $game = GameFactory::createOne(['createdBy' => $user, 'difficulty' => $difficulty]);
        UserGameFactory::createOne(['user' => $user, 'game' => $game, 'role' => UserGameRole::Host]);

        $this->expectException(ConflictException::class);
        $this->userGameService->joinGame($user->_real(), $game->_real());
    }
}
