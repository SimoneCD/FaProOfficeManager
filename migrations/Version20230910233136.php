<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230910233136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reminder (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, invoice_id INT DEFAULT NULL, send_at DATETIME NOT NULL, depricated_at DATETIME NOT NULL, title VARCHAR(255) NOT NULL, INDEX IDX_40374F409395C3F3 (customer_id), INDEX IDX_40374F402989F1FD (invoice_id), INDEX send_at_idx (send_at), INDEX depricated_at_idx (depricated_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reminder ADD CONSTRAINT FK_40374F409395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reminder ADD CONSTRAINT FK_40374F402989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reminder DROP FOREIGN KEY FK_40374F409395C3F3');
        $this->addSql('ALTER TABLE reminder DROP FOREIGN KEY FK_40374F402989F1FD');
        $this->addSql('DROP TABLE reminder');
    }
}
