<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // This fixture just coordinates the loading order
        // All actual data is loaded by dependent fixtures
    }

    public function getDependencies(): array
    {
        return [
            UserAccountFixtures::class,
            CardFixtures::class,
            TeamMemberFixtures::class,
            CardAssignmentFixtures::class,
        ];
    }
}
