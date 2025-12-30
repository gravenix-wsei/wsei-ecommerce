<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251230182546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Grant ROLE_SUPER_ADMIN to admin user';
    }

    public function up(Schema $schema): void
    {
        // Grant ROLE_SUPER_ADMIN to the default admin user
        $this->addSql("UPDATE user SET roles = JSON_ARRAY('ROLE_SUPER_ADMIN') WHERE email = 'admin'");
    }

    public function down(Schema $schema): void
    {
        // Revert admin user roles to empty array (ROLE_ADMIN is guaranteed by getRoles() method in User entity)
        $this->addSql("UPDATE user SET roles = JSON_ARRAY('ROLE_ADMIN') WHERE email = 'admin'");
    }
}
