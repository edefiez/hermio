<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function findValidToken(string $token): ?PasswordResetToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.token = :token')
            ->andWhere('t.isUsed = :used')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('used', false)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function invalidateUserTokens(User $user): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.isUsed', ':used')
            ->where('t.user = :user')
            ->andWhere('t.isUsed = :notUsed')
            ->setParameter('used', true)
            ->setParameter('notUsed', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function deleteExpiredTokens(): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < :weekAgo')
            ->setParameter('weekAgo', new \DateTime('-7 days'))
            ->getQuery()
            ->execute();
    }
}
