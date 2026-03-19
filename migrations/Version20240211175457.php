<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240211175457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE settings (id INT NOT NULL, company_name VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, active TINYINT(1) NOT NULL, slack_bearer VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, email_name VARCHAR(255) DEFAULT NULL, customer_id_start INT DEFAULT NULL, holidays_bundesland VARCHAR(255) DEFAULT NULL, app_active_log LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', logo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE settings');
    }
}
