<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230927000737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE link (id INT AUTO_INCREMENT NOT NULL, sex VARCHAR(20) DEFAULT NULL, fullname VARCHAR(255) NOT NULL, notice LONGTEXT DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, `key` VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact_person ADD `key` VARCHAR(255) DEFAULT NULL, CHANGE customer_id customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer ADD link_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09ADA40271 FOREIGN KEY (link_id) REFERENCES link (id)');
        $this->addSql('CREATE INDEX IDX_81398E09ADA40271 ON customer (link_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09ADA40271');
        $this->addSql('DROP TABLE link');
        $this->addSql('ALTER TABLE contact_person DROP `key`, CHANGE customer_id customer_id INT NOT NULL');
        $this->addSql('DROP INDEX IDX_81398E09ADA40271 ON customer');
        $this->addSql('ALTER TABLE customer DROP link_id');
    }
}
