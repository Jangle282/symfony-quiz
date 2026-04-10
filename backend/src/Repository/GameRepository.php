<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Difficulty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
        $game->setTotalScore(0);
        $game->setStartedAt(new \DateTimeImmutable());
        $this->getEntityManager()->persist($game);
        $this->getEntityManager()->flush();
        return $game;
    }

//    /**
//     * @return Game[] Returns an array of Game objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('g.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Game
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}