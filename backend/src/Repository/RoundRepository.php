<?php

namespace App\Repository;

use App\Entity\Round;
use App\Entity\Game;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Round>
 */
class RoundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Round::class);
    }

    public function create(Game $game, Category $category): Round
    {
        $round = new Round();
        $round->setGame($game);
        $round->setCategory($category);
        $round->setRoundNumber(1);
        $this->getEntityManager()->persist($round);
        $game->addRound($round);
        $this->getEntityManager()->flush();
        return $round;
    }
}