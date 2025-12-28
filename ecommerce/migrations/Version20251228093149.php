<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251228093149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_session table for Stripe payment integration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payment_session (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(128) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, stripe_session_id VARCHAR(255) DEFAULT NULL, payment_intent_id VARCHAR(255) DEFAULT NULL, return_url LONGTEXT NOT NULL, order_id INT NOT NULL, UNIQUE INDEX UNIQ_C90DDB3E5F37A13B (token), UNIQUE INDEX UNIQ_C90DDB3E8D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE payment_session ADD CONSTRAINT FK_C90DDB3E8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_session DROP FOREIGN KEY FK_C90DDB3E8D9F6D38');
        $this->addSql('DROP TABLE payment_session');
    }
}

