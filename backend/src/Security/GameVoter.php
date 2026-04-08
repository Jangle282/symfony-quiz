<?php

namespace App\Security;

use App\Entity\Game;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GameVoter extends Voter
{
    public const VIEW = 'GAME_VIEW';
    public const DELETE = 'GAME_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::DELETE], true) && $subject instanceof Game;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Game) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->isParticipant($user, $subject),
            self::DELETE => $this->isHost($user, $subject),
            default => false,
        };
    }

    private function isParticipant(User $user, Game $game): bool
    {
        foreach ($game->getUserGames() as $userGame) {
            if ($userGame->getUser()?->getId()?->toString() === $user->getId()?->toString()) {
                return true;
            }
        }

        return $game->getCreatedBy()?->getId()?->toString() === $user->getId()?->toString();
    }

    private function isHost(User $user, Game $game): bool
    {
        return $game->getCreatedBy()?->getId()?->toString() === $user->getId()?->toString();
    }
}
