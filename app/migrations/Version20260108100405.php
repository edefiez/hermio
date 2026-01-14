<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create card_views table for tracking card views
 */
final class Version20260108100405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create card_views table for tracking card views';
    }

    public function up(Schema $schema): void
    {
        // Create card_views table
        $this->addSql('CREATE TABLE card_views (
            id INT AUTO_INCREMENT NOT NULL,
            card_id INT NOT NULL,
            viewed_at DATETIME NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            country VARCHAR(10) DEFAULT NULL,
            INDEX idx_card_viewed_at (card_id, viewed_at),
            INDEX idx_viewed_at (viewed_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE card_views ADD CONSTRAINT FK_CARD_VIEWS_CARD FOREIGN KEY (card_id) REFERENCES cards (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraint
        $this->addSql('ALTER TABLE card_views DROP FOREIGN KEY FK_CARD_VIEWS_CARD');
        
        // Drop table
        $this->addSql('DROP TABLE card_views');
    }
}

