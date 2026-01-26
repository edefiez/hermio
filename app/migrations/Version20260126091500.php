<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add branding enhancements: second logo, font selection, and card templates
 */
final class Version20260126091500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add second logo, font selection, and card template fields to account_branding table';
    }

    public function up(Schema $schema): void
    {
        // Add second logo fields
        $this->addSql('ALTER TABLE account_branding ADD second_logo_filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE account_branding ADD second_logo_position VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE account_branding ADD second_logo_size VARCHAR(20) DEFAULT NULL');
        
        // Add font fields
        $this->addSql('ALTER TABLE account_branding ADD font_family VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE account_branding ADD custom_font_filename VARCHAR(255) DEFAULT NULL');
        
        // Add card template field
        $this->addSql('ALTER TABLE account_branding ADD card_template VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove added fields
        $this->addSql('ALTER TABLE account_branding DROP second_logo_filename');
        $this->addSql('ALTER TABLE account_branding DROP second_logo_position');
        $this->addSql('ALTER TABLE account_branding DROP second_logo_size');
        $this->addSql('ALTER TABLE account_branding DROP font_family');
        $this->addSql('ALTER TABLE account_branding DROP custom_font_filename');
        $this->addSql('ALTER TABLE account_branding DROP card_template');
    }
}
