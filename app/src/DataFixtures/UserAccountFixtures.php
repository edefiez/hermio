<?php

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\User;
use App\Enum\PlanType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserAccountFixtures extends Fixture
{
    public const FREE_USERS_COUNT = 20;
    public const PRO_USERS_COUNT = 20;
    public const ENTERPRISE_USERS_COUNT = 20;

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        // Create FREE plan users
        for ($i = 1; $i <= self::FREE_USERS_COUNT; $i++) {
            $user = $this->createUser($faker, "free_user_$i@example.com", "free_user_$i");
            $account = $this->createAccount($user, PlanType::FREE);
            
            $manager->persist($user);
            $manager->persist($account);
            
            $this->addReference("user_free_$i", $user);
            $this->addReference("account_free_$i", $account);
        }

        // Create PRO plan users
        for ($i = 1; $i <= self::PRO_USERS_COUNT; $i++) {
            $user = $this->createUser($faker, "pro_user_$i@example.com", "pro_user_$i");
            $account = $this->createAccount($user, PlanType::PRO);
            
            $manager->persist($user);
            $manager->persist($account);
            
            $this->addReference("user_pro_$i", $user);
            $this->addReference("account_pro_$i", $account);
        }

        // Create ENTERPRISE plan users
        for ($i = 1; $i <= self::ENTERPRISE_USERS_COUNT; $i++) {
            $user = $this->createUser($faker, "enterprise_user_$i@example.com", "enterprise_user_$i");
            $account = $this->createAccount($user, PlanType::ENTERPRISE);
            
            $manager->persist($user);
            $manager->persist($account);
            
            $this->addReference("user_enterprise_$i", $user);
            $this->addReference("account_enterprise_$i", $account);
        }

        $manager->flush();
    }

    private function createUser(\Faker\Generator $faker, string $email, string $username): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setRoles(['ROLE_USER']);
        $user->setIsEmailVerified(true);
        $user->setStatus('active');
        
        // Set random creation date within last 6 months
        $createdAt = $faker->dateTimeBetween('-6 months', 'now');
        $user->setCreatedAt($createdAt);
        
        // Random last login (some users never logged in)
        if ($faker->boolean(70)) {
            $lastLogin = $faker->dateTimeBetween($createdAt, 'now');
            $user->setLastLoginAt($lastLogin);
        }

        return $user;
    }

    private function createAccount(User $user, PlanType $planType): Account
    {
        $account = new Account();
        $account->setUser($user);
        $account->setPlanType($planType);
        $account->setCreatedAt($user->getCreatedAt());

        return $account;
    }
}

