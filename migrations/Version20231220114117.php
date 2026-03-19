<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231220114117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reminder DROP FOREIGN KEY FK_40374F402989F1FD');
        $this->addSql('ALTER TABLE reminder DROP FOREIGN KEY FK_40374F409395C3F3');
        $this->addSql('DROP TABLE reminder');
        $this->addSql('ALTER TABLE contact_person DROP FOREIGN KEY FK_A44EE6F79395C3F3');
        $this->addSql('DROP INDEX IDX_A44EE6F79395C3F3 ON contact_person');
        $this->addSql('ALTER TABLE contact_person ADD text LONGTEXT DEFAULT NULL, ADD type VARCHAR(55) DEFAULT NULL, ADD created_at DATETIME NOT NULL, DROP customer_id, DROP `key`, CHANGE surname sur_name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reminder (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, invoice_id INT DEFAULT NULL, send_at DATETIME NOT NULL, depricated_at DATETIME NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_40374F402989F1FD (invoice_id), INDEX send_at_idx (send_at), INDEX depricated_at_idx (depricated_at), INDEX IDX_40374F409395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE reminder ADD CONSTRAINT FK_40374F402989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reminder ADD CONSTRAINT FK_40374F409395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contact_person ADD customer_id INT DEFAULT NULL, ADD `key` VARCHAR(255) DEFAULT NULL, DROP text, DROP type, DROP created_at, CHANGE sur_name surname VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE contact_person ADD CONSTRAINT FK_A44EE6F79395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('CREATE INDEX IDX_A44EE6F79395C3F3 ON contact_person (customer_id)');
    }
}
