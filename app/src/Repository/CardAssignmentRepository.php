<?php

namespace App\Repository;

use App\Entity\Card;
use App\Entity\CardAssignment;
use App\Entity\TeamMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardAssignment>
 */
class CardAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardAssignment::class);
    }

    /**
     * Find all assignments for a card
     *
     * @return CardAssignment[]
     */
    public function findByCard(Card $card): array
    {
        return $this->createQueryBuilder('ca')
            ->where('ca.card = :card')
            ->setParameter('card', $card)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all assignments for a team member
     *
     * @return CardAssignment[]
     */
    public function findByTeamMember(TeamMember $teamMember): array
    {
        return $this->createQueryBuilder('ca')
            ->where('ca.teamMember = :teamMember')
            ->setParameter('teamMember', $teamMember)
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a card is assigned to a team member
     */
    public function isAssignedTo(Card $card, TeamMember $teamMember): bool
    {
        return $this->createQueryBuilder('ca')
            ->select('COUNT(ca.id)')
            ->where('ca.card = :card')
            ->andWhere('ca.teamMember = :teamMember')
            ->setParameter('card', $card)
            ->setParameter('teamMember', $teamMember)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}

