<?php

namespace App\DataFixtures;

use App\Entity\TeamMember;
use App\Entity\User;
use App\Enum\TeamRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TeamMemberFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        // Create team members only for ENTERPRISE accounts
        for ($i = 1; $i <= UserAccountFixtures::ENTERPRISE_USERS_COUNT; $i++) {
            /** @var \App\Entity\Account $account */
            $account = $this->getReference("account_enterprise_$i", \App\Entity\Account::class);
            
            // Each Enterprise account has 2-8 team members
            $teamMemberCount = $faker->numberBetween(2, 8);
            
            for ($j = 1; $j <= $teamMemberCount; $j++) {
                $teamMember = $this->createTeamMember($account, $faker, $j);
                $manager->persist($teamMember);
                $this->addReference("team_member_enterprise_{$i}_{$j}", $teamMember);
            }
        }

        $manager->flush();
    }

    private function createTeamMember(\App\Entity\Account $account, \Faker\Generator $faker, int $index): TeamMember
    {
        $teamMember = new TeamMember();
        $teamMember->setAccount($account);
        $teamMember->setEmail($faker->unique()->email());
        
        // First team member is ADMIN, others are MEMBER
        $role = $index === 1 ? TeamRole::ADMIN : TeamRole::MEMBER;
        $teamMember->setRole($role);
        
        // 70% accepted, 20% pending, 10% declined/expired
        $statusRand = $faker->numberBetween(1, 100);
        if ($statusRand <= 70) {
            // Accepted - link to a user if possible
            $teamMember->setInvitationStatus('accepted');
            $teamMember->setJoinedAt($faker->dateTimeBetween('-3 months', 'now'));
            $teamMember->setLastActivityAt($faker->optional(0.8)->dateTimeBetween('-1 month', 'now'));
            
            // Try to link to an existing user (30% chance)
            if ($faker->boolean(30)) {
                // Link to a random user (could be from any plan)
                try {
                    $randomUserRef = $this->getRandomUserReference();
                    if ($randomUserRef) {
                        /** @var User $randomUser */
                        $randomUser = $this->getReference($randomUserRef, User::class);
                        $teamMember->setUser($randomUser);
                        $teamMember->setEmail($randomUser->getEmail());
                    }
                } catch (\OutOfBoundsException $e) {
                    // Reference doesn't exist yet, skip linking
                }
            }
        } elseif ($statusRand <= 90) {
            // Pending
            $teamMember->setInvitationStatus('pending');
            $teamMember->setInvitationToken(bin2hex(random_bytes(32)));
            $teamMember->setInvitationExpiresAt($faker->dateTimeBetween('now', '+7 days'));
        } else {
            // Declined or expired
            $teamMember->setInvitationStatus($faker->randomElement(['declined', 'expired']));
            if ($teamMember->getInvitationStatus() === 'expired') {
                $teamMember->setInvitationExpiresAt($faker->dateTimeBetween('-7 days', '-1 day'));
            }
        }

        return $teamMember;
    }

    private function getRandomUserReference(): ?string
    {
        $references = [];
        
        // Collect all user references
        for ($i = 1; $i <= UserAccountFixtures::FREE_USERS_COUNT; $i++) {
            $references[] = "user_free_$i";
        }
        for ($i = 1; $i <= UserAccountFixtures::PRO_USERS_COUNT; $i++) {
            $references[] = "user_pro_$i";
        }
        for ($i = 1; $i <= UserAccountFixtures::ENTERPRISE_USERS_COUNT; $i++) {
            $references[] = "user_enterprise_$i";
        }
        
        if (empty($references)) {
            return null;
        }
        
        return $references[array_rand($references)];
    }

    public function getDependencies(): array
    {
        return [
            UserAccountFixtures::class,
        ];
    }
}

