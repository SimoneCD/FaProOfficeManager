<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240118160554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reminder (id INT AUTO_INCREMENT NOT NULL, invoice_id INT DEFAULT NULL, user_id INT DEFAULT NULL, customer_id INT DEFAULT NULL, send_date DATETIME DEFAULT NULL, context LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', type VARCHAR(255) NOT NULL, number VARCHAR(255) DEFAULT NULL, text LONGTEXT DEFAULT NULL, INDEX IDX_40374F402989F1FD (invoice_id), INDEX IDX_40374F40A76ED395 (user_id), INDEX IDX_40374F409395C3F3 (customer_id), INDEX create_date_idx (send_date), INDEX type_idx (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reminder ADD CONSTRAINT FK_40374F402989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reminder ADD CONSTRAINT FK_40374F40A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE reminder ADD CONSTRAINT FK_40374F409395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reminder DROP FOREIGN KEY FK_40374F402989F1FD');
        $this->addSql('ALTER TABLE reminder DROP FOREIGN KEY FK_40374F40A76ED395');
        $this->addSql('ALTER TABLE reminder DROP FOREIGN KEY FK_40374F409395C3F3');
        $this->addSql('DROP TABLE reminder');
    }
}
