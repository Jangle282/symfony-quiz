<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use App\Entity\UserGame;
use App\Entity\UserGameRole;
use App\Exception\ConflictException;
use Doctrine\ORM\EntityManagerInterface;

class UserGameService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function joinGame(User $user, Game $game): UserGame
    {
        foreach ($game->getUserGames() as $userGame) {
            if ($userGame->getUser()?->getId()?->toString() === $user->getId()?->toString()) {
                throw new ConflictException('You have already joined this game.');
            }
        }

        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);
        $userGame->setRole(UserGameRole::Participant);
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();

        return $userGame;
    }
}
