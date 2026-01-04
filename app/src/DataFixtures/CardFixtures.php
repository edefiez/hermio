<?php

namespace App\DataFixtures;

use App\Entity\Card;
use App\Enum\PlanType;
use App\Service\SecureKeyGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;

class CardFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private SecureKeyGenerator $keyGenerator
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        // Create cards for FREE users (max 1 per user)
        for ($i = 1; $i <= UserAccountFixtures::FREE_USERS_COUNT; $i++) {
            /** @var \App\Entity\User $user */
            $user = $this->getReference("user_free_$i", \App\Entity\User::class);

            // FREE plan: max 1 card, but not all users have cards
            if ($faker->boolean(80)) {
                $card = $this->createCard($user, $faker, "free-card-$i");
                $manager->persist($card);
                $this->addReference("card_free_$i", $card);
            }
        }

        // Create cards for PRO users (max 10 per user)
        for ($i = 1; $i <= UserAccountFixtures::PRO_USERS_COUNT; $i++) {
            /** @var \App\Entity\User $user */
            $user = $this->getReference("user_pro_$i", \App\Entity\User::class);

            // PRO plan: max 10 cards, users have between 1-10 cards
            $cardCount = $faker->numberBetween(1, 10);
            for ($j = 1; $j <= $cardCount; $j++) {
                $card = $this->createCard($user, $faker, "pro-card-$i-$j");
                $manager->persist($card);
                $this->addReference("card_pro_{$i}_{$j}", $card);
            }
        }

        // Create cards for ENTERPRISE users (unlimited)
        for ($i = 1; $i <= UserAccountFixtures::ENTERPRISE_USERS_COUNT; $i++) {
            /** @var \App\Entity\User $user */
            $user = $this->getReference("user_enterprise_$i", \App\Entity\User::class);

            // ENTERPRISE plan: unlimited cards, users have between 5-30 cards
            $cardCount = $faker->numberBetween(5, 30);
            for ($j = 1; $j <= $cardCount; $j++) {
                $card = $this->createCard($user, $faker, "enterprise-card-$i-$j");
                $manager->persist($card);
                $this->addReference("card_enterprise_{$i}_{$j}", $card);
            }
        }

        $manager->flush();
    }

    /**
     * @throws Exception
     */
    private function createCard(\App\Entity\User $user, \Faker\Generator $faker, string $slugBase): Card
    {
        $card = new Card();
        $card->setUser($user);
        $card->setSlug($slugBase . '-' . uniqid());
        $card->setStatus('active');

        // Generate public access key
        $card->setPublicAccessKey($this->keyGenerator->generateRandomKey());

        // Create realistic card content
        $content = [
            'name' => $faker->name(),
            'email' => $faker->email(),
            'phone' => $faker->phoneNumber(),
            'company' => $faker->company(),
            'title' => $faker->jobTitle(),
            'bio' => $faker->optional(0.7)->text(200),
            'website' => $faker->optional(0.5)->url(),
            // All social fields in social object
            'social' => [
                'linkedin' => $faker->optional(0.6)->url(),
                'instagram' => $faker->optional(0.5)->url(),
                'facebook' => $faker->optional(0.4)->url(),
                'tiktok' => $faker->optional(0.3)->url(),
            ],
        ];

        $card->setContent($content);

        // Set random creation date
        $createdAt = $faker->dateTimeBetween('-6 months', 'now');
        $card->setCreatedAt($createdAt);
        $card->setUpdatedAt($faker->dateTimeBetween($createdAt, 'now'));

        return $card;
    }

    public function getDependencies(): array
    {
        return [
            UserAccountFixtures::class,
        ];
    }
}

