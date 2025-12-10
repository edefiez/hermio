<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210091439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account_branding (id INT AUTO_INCREMENT NOT NULL, primary_color VARCHAR(7) DEFAULT NULL, secondary_color VARCHAR(7) DEFAULT NULL, logo_filename VARCHAR(255) DEFAULT NULL, logo_position VARCHAR(20) DEFAULT NULL, logo_size VARCHAR(20) DEFAULT NULL, custom_template LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, account_id INT NOT NULL, UNIQUE INDEX account_branding_unique (account_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE account_branding ADD CONSTRAINT FK_CA956CE19B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account_branding DROP FOREIGN KEY FK_CA956CE19B6B5FBA');
        $this->addSql('DROP TABLE account_branding');
    }
}
