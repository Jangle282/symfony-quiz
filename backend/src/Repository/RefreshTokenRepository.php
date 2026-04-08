<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findOneByToken(string $token): ?RefreshToken
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * Revoke all non-revoked tokens for a user
     */
    public function revokeAllForUser(User $user): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->update(RefreshToken::class, 'r')
            ->set('r.revoked', 'true')
            ->where('r.user = :user')
            ->andWhere('r.revoked = false')
            ->setParameter('user', $user);

        return $qb->getQuery()->execute();
    }
}
