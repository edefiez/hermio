<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208083057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE authentication_logs (id INT AUTO_INCREMENT NOT NULL, event_type VARCHAR(50) NOT NULL, timestamp DATETIME NOT NULL, ip_address VARCHAR(45) NOT NULL, user_agent VARCHAR(500) NOT NULL, details LONGTEXT DEFAULT NULL, successful TINYINT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_5E2319D8A76ED395 (user_id), INDEX idx_event_timestamp (event_type, timestamp), INDEX idx_user_timestamp (user_id, timestamp), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE email_verification_tokens (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, is_used TINYINT NOT NULL, used_at DATETIME DEFAULT NULL, email VARCHAR(180) NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_C81CA2AC5F37A13B (token), INDEX IDX_C81CA2ACA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE password_reset_tokens (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, is_used TINYINT NOT NULL, used_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_3967A2165F37A13B (token), INDEX IDX_3967A216A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_email_verified TINYINT NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE authentication_logs ADD CONSTRAINT FK_5E2319D8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE email_verification_tokens ADD CONSTRAINT FK_C81CA2ACA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_3967A216A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE authentication_logs DROP FOREIGN KEY FK_5E2319D8A76ED395');
        $this->addSql('ALTER TABLE email_verification_tokens DROP FOREIGN KEY FK_C81CA2ACA76ED395');
        $this->addSql('ALTER TABLE password_reset_tokens DROP FOREIGN KEY FK_3967A216A76ED395');
        $this->addSql('DROP TABLE authentication_logs');
        $this->addSql('DROP TABLE email_verification_tokens');
        $this->addSql('DROP TABLE password_reset_tokens');
        $this->addSql('DROP TABLE users');
    }
}
