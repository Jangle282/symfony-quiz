<?php

namespace App\Repository;

use App\Entity\Question;
use App\Entity\Round;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function findNextInRound(Round $round, UuidInterface $afterQuestionId): ?Question
    {
        return $this->createQueryBuilder('q')
            ->where('q.round = :round')
            ->andWhere('q.id > :questionId')
            ->setParameter('round', $round)
            ->setParameter('questionId', $afterQuestionId)
            ->orderBy('q.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPreviousInRound(Round $round, UuidInterface $beforeQuestionId): ?Question
    {
        return $this->createQueryBuilder('q')
            ->where('q.round = :round')
            ->andWhere('q.id < :questionId')
            ->setParameter('round', $round)
            ->setParameter('questionId', $beforeQuestionId)
            ->orderBy('q.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}