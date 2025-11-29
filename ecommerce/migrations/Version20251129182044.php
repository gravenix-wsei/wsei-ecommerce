<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129182044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user table and add admin user';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        // Insert admin user with hashed password (username: admin, password: password)
        $this->addSql("INSERT INTO user (email, roles, password) VALUES ('admin', '[\"ROLE_ADMIN\"]', '\$2y\$13\$1jNuwZbjOIK3O5A330Qy2.NfVPVa5VBDzZfZxQHudy5mpY3XC4OS2')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM user WHERE email = 'admin'");
        $this->addSql('DROP TABLE user');
    }
}
