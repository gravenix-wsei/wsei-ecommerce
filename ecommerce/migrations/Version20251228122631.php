<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251228122631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_session DROP INDEX UNIQ_C90DDB3E8D9F6D38, ADD INDEX IDX_C90DDB3E8D9F6D38 (order_id)');
        $this->addSql('ALTER TABLE payment_session ADD status VARCHAR(20) NOT NULL DEFAULT \'active\'');
        // Update existing records to have active status
        $this->addSql('UPDATE payment_session SET status = \'active\' WHERE status = \'\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_session DROP INDEX IDX_C90DDB3E8D9F6D38, ADD UNIQUE INDEX UNIQ_C90DDB3E8D9F6D38 (order_id)');
        $this->addSql('ALTER TABLE payment_session DROP status');
    }
}
