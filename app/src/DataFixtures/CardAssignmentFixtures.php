<?php

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\CardAssignment;
use App\Entity\TeamMember;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CardAssignmentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        // Create card assignments only for ENTERPRISE accounts
        for ($i = 1; $i <= UserAccountFixtures::ENTERPRISE_USERS_COUNT; $i++) {
            /** @var \App\Entity\Account $account */
            $account = $this->getReference("account_enterprise_$i", \App\Entity\Account::class);
            /** @var \App\Entity\User $accountOwner */
            $accountOwner = $account->getUser();
            
            // Get all accepted team members for this account
            $teamMembers = [];
            for ($j = 1; $j <= 8; $j++) {
                try {
                    /** @var TeamMember $teamMember */
                    $teamMember = $this->getReference("team_member_enterprise_{$i}_{$j}", TeamMember::class);
                    if ($teamMember->getInvitationStatus() === 'accepted') {
                        $teamMembers[] = $teamMember;
                    }
                } catch (\OutOfBoundsException $e) {
                    // Reference doesn't exist, skip
                    break;
                }
            }
            
            if (empty($teamMembers)) {
                continue;
            }
            
            // Get all cards for this Enterprise account
            $cards = [];
            for ($j = 1; $j <= 30; $j++) {
                try {
                    /** @var Card $card */
                    $card = $this->getReference("card_enterprise_{$i}_{$j}", Card::class);
                    $cards[] = $card;
                } catch (\OutOfBoundsException $e) {
                    // No more cards
                    break;
                }
            }
            
            if (empty($cards)) {
                continue;
            }
            
            // Assign cards to team members
            // Each card can be assigned to 0-3 team members
            foreach ($cards as $card) {
                $assignmentCount = $faker->numberBetween(0, min(3, count($teamMembers)));
                
                if ($assignmentCount > 0) {
                    $selectedMembers = $faker->randomElements($teamMembers, $assignmentCount);
                    
                    foreach ($selectedMembers as $teamMember) {
                        $assignment = new CardAssignment();
                        $assignment->setCard($card);
                        $assignment->setTeamMember($teamMember);
                        $assignment->setAssignedBy($accountOwner);
                        $assignment->setAssignedAt($faker->dateTimeBetween('-2 months', 'now'));
                        
                        $manager->persist($assignment);
                    }
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserAccountFixtures::class,
            CardFixtures::class,
            TeamMemberFixtures::class,
        ];
    }
}

