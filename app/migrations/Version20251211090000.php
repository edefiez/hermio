<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add public_access_key column to cards table for secure public access
 */
final class Version20251211090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add public_access_key column to cards table for secure public card access';
    }

    public function up(Schema $schema): void
    {
        // Add public_access_key column (nullable to allow gradual migration)
        $this->addSql('ALTER TABLE cards ADD public_access_key VARCHAR(128) DEFAULT NULL');
        
        // Add index for performance (optional but recommended for lookups)
        $this->addSql('CREATE INDEX IDX_4C258FD_PUBLIC_KEY ON cards (public_access_key)');
    }

    public function down(Schema $schema): void
    {
        // Remove index first
        $this->addSql('DROP INDEX IDX_4C258FD_PUBLIC_KEY ON cards');
        
        // Remove column
        $this->addSql('ALTER TABLE cards DROP public_access_key');
    }
}
