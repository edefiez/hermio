<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210103541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE card_assignments (id INT AUTO_INCREMENT NOT NULL, assigned_at DATETIME NOT NULL, card_id INT NOT NULL, team_member_id INT NOT NULL, assigned_by_id INT NOT NULL, INDEX IDX_D75E0F474ACC9A20 (card_id), INDEX IDX_D75E0F47C292CD19 (team_member_id), INDEX IDX_D75E0F476E6F1246 (assigned_by_id), UNIQUE INDEX card_team_member_unique (card_id, team_member_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE team_members (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, role VARCHAR(20) NOT NULL, invitation_status VARCHAR(20) NOT NULL, invitation_token VARCHAR(64) DEFAULT NULL, invitation_expires_at DATETIME DEFAULT NULL, joined_at DATETIME DEFAULT NULL, last_activity_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, account_id INT NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_BAD9A3C833FC351A (invitation_token), INDEX IDX_BAD9A3C89B6B5FBA (account_id), INDEX IDX_BAD9A3C8A76ED395 (user_id), UNIQUE INDEX account_email_unique (account_id, email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE card_assignments ADD CONSTRAINT FK_D75E0F474ACC9A20 FOREIGN KEY (card_id) REFERENCES cards (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE card_assignments ADD CONSTRAINT FK_D75E0F47C292CD19 FOREIGN KEY (team_member_id) REFERENCES team_members (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE card_assignments ADD CONSTRAINT FK_D75E0F476E6F1246 FOREIGN KEY (assigned_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C89B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE card_assignments DROP FOREIGN KEY FK_D75E0F474ACC9A20');
        $this->addSql('ALTER TABLE card_assignments DROP FOREIGN KEY FK_D75E0F47C292CD19');
        $this->addSql('ALTER TABLE card_assignments DROP FOREIGN KEY FK_D75E0F476E6F1246');
        $this->addSql('ALTER TABLE team_members DROP FOREIGN KEY FK_BAD9A3C89B6B5FBA');
        $this->addSql('ALTER TABLE team_members DROP FOREIGN KEY FK_BAD9A3C8A76ED395');
        $this->addSql('DROP TABLE card_assignments');
        $this->addSql('DROP TABLE team_members');
    }
}
