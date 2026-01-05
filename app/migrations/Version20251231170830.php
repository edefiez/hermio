<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251231170830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE card_scans RENAME INDEX idx_card_scans_card TO IDX_129003914ACC9A20');
        $this->addSql('DROP INDEX IDX_4C258FD_PUBLIC_KEY ON cards');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_4C258FD_PUBLIC_KEY ON cards (public_access_key)');
        $this->addSql('ALTER TABLE card_scans RENAME INDEX idx_129003914acc9a20 TO IDX_CARD_SCANS_CARD');
    }
}
