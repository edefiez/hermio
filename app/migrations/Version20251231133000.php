<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add card_scans table for tracking QR code scans
 */
final class Version20251231133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add card_scans table for tracking QR code scans with analytics data';
    }

    public function up(Schema $schema): void
    {
        // Create card_scans table
        $this->addSql('CREATE TABLE card_scans (
            id INT AUTO_INCREMENT NOT NULL,
            card_id INT NOT NULL,
            scanned_at DATETIME NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            country VARCHAR(10) DEFAULT NULL,
            INDEX idx_card_scanned_at (card_id, scanned_at),
            INDEX idx_scanned_at (scanned_at),
            INDEX IDX_CARD_SCANS_CARD (card_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign key constraint
        $this->addSql('ALTER TABLE card_scans ADD CONSTRAINT FK_CARD_SCANS_CARD 
            FOREIGN KEY (card_id) REFERENCES cards (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraint first
        $this->addSql('ALTER TABLE card_scans DROP FOREIGN KEY FK_CARD_SCANS_CARD');
        
        // Drop table
        $this->addSql('DROP TABLE card_scans');
    }
}
