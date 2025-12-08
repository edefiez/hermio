<?php

namespace App\Repository;

use App\Entity\AuthenticationLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthenticationLog>
 */
class AuthenticationLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthenticationLog::class);
    }

    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findFailedLoginAttempts(string $ipAddress, \DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.eventType = :eventType')
            ->andWhere('l.ipAddress = :ipAddress')
            ->andWhere('l.successful = :successful')
            ->andWhere('l.timestamp >= :since')
            ->setParameter('eventType', AuthenticationLog::EVENT_LOGIN_FAILURE)
            ->setParameter('ipAddress', $ipAddress)
            ->setParameter('successful', false)
            ->setParameter('since', $since)
            ->orderBy('l.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countRecentFailedAttempts(string $ipAddress, int $minutes = 15): int
    {
        $since = new \DateTime("-{$minutes} minutes");
        
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.eventType = :eventType')
            ->andWhere('l.ipAddress = :ipAddress')
            ->andWhere('l.successful = :successful')
            ->andWhere('l.timestamp >= :since')
            ->setParameter('eventType', AuthenticationLog::EVENT_LOGIN_FAILURE)
            ->setParameter('ipAddress', $ipAddress)
            ->setParameter('successful', false)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteOldLogs(int $days = 90): int
    {
        $date = new \DateTime("-{$days} days");
        
        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.timestamp < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
