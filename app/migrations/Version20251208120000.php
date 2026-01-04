<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Create accounts table for subscription management
 */
final class Version20251208120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create accounts table with subscription plan types and quota management';
    }

    public function up(Schema $schema): void
    {
        // Create accounts table
        $this->addSql('CREATE TABLE accounts (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            plan_type VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            updated_by VARCHAR(180) DEFAULT NULL,
            UNIQUE INDEX UNIQ_ACCOUNTS_USER_ID (user_id),
            INDEX idx_plan_type (plan_type),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign key constraint
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT FK_ACCOUNTS_USER_ID FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        // Create Account records for all existing users with FREE plan
        $this->addSql("INSERT INTO accounts (user_id, plan_type, created_at, updated_at, updated_by)
            SELECT id, 'free', created_at, NULL, NULL FROM users WHERE id NOT IN (SELECT user_id FROM accounts)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts DROP FOREIGN KEY FK_ACCOUNTS_USER_ID');
        $this->addSql('DROP TABLE accounts');
    }
}

