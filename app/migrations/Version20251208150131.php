<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208150131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payments (id INT AUTO_INCREMENT NOT NULL, stripe_payment_intent_id VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, amount INT NOT NULL, currency VARCHAR(3) NOT NULL, plan_type VARCHAR(20) DEFAULT NULL, paid_at DATETIME NOT NULL, created_at DATETIME NOT NULL, stripe_event_data LONGTEXT DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_65D29B32A76ED395 (user_id), UNIQUE INDEX stripe_payment_intent_unique (stripe_payment_intent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE processed_webhook_events (id INT AUTO_INCREMENT NOT NULL, stripe_event_id VARCHAR(255) NOT NULL, event_type VARCHAR(100) NOT NULL, processed_at DATETIME NOT NULL, success TINYINT NOT NULL, error_message LONGTEXT DEFAULT NULL, UNIQUE INDEX stripe_event_id_unique (stripe_event_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stripe_customers (id INT AUTO_INCREMENT NOT NULL, stripe_customer_id VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_DDDE68EB708DC647 (stripe_customer_id), UNIQUE INDEX user_stripe_customer_unique (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE subscriptions (id INT AUTO_INCREMENT NOT NULL, stripe_subscription_id VARCHAR(255) NOT NULL, plan_type VARCHAR(20) NOT NULL, status VARCHAR(50) NOT NULL, current_period_start DATETIME NOT NULL, current_period_end DATETIME NOT NULL, canceled_at DATETIME DEFAULT NULL, cancel_at_period_end DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX user_subscription_unique (user_id), UNIQUE INDEX stripe_subscription_unique (stripe_subscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stripe_customers ADD CONSTRAINT FK_DDDE68EBA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT FK_4778A01A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_plan_type ON accounts');
        $this->addSql('ALTER TABLE accounts RENAME INDEX uniq_accounts_user_id TO user_account_unique');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32A76ED395');
        $this->addSql('ALTER TABLE stripe_customers DROP FOREIGN KEY FK_DDDE68EBA76ED395');
        $this->addSql('ALTER TABLE subscriptions DROP FOREIGN KEY FK_4778A01A76ED395');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE processed_webhook_events');
        $this->addSql('DROP TABLE stripe_customers');
        $this->addSql('DROP TABLE subscriptions');
        $this->addSql('CREATE INDEX idx_plan_type ON accounts (plan_type)');
        $this->addSql('ALTER TABLE accounts RENAME INDEX user_account_unique TO UNIQ_ACCOUNTS_USER_ID');
    }
}
