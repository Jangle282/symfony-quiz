<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Difficulty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function create(User $user, Difficulty $difficulty, Category $category, ?string $name = null): Game
    {
        $game = new Game();
        $game->setName($name);
        $game->setDifficulty($difficulty);
        $game->setCreatedBy($user);
        $game->setStartedAt(new \DateTimeImmutable());
        $this->getEntityManager()->persist($game);
        $this->getEntityManager()->flush();
        return $game;
    }

    public function getGameWithRoundsAndQuestions(UuidInterface $gameId): ?Game
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.rounds', 'r')
            ->addSelect('r')
            ->leftJoin('r.questions', 'q')
            ->addSelect('q')
            ->leftJoin('q.answers', 'a')
            ->addSelect('a')
            ->leftJoin('r.category', 'c')
            ->addSelect('c')
            ->leftJoin('g.difficulty', 'd')
            ->addSelect('d')
            ->where('g.id = :gameId')
            ->setParameter('gameId', $gameId)
            ->orderBy('r.roundNumber', 'ASC')
            ->addOrderBy('q.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}