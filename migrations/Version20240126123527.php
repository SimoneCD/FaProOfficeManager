<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240126123527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_E7927C749395C3F3');
        $this->addSql('DROP INDEX IDX_E7927C749395C3F3 ON email');
        $this->addSql('ALTER TABLE email CHANGE customer_id send_from_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_E7927C74AE87AC65 FOREIGN KEY (send_from_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E7927C74AE87AC65 ON email (send_from_id)');
        $this->addSql('ALTER TABLE `email` MODIFY COLUMN `send_from_id` INT(11) DEFAULT NULL AFTER `send_to_id`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_E7927C74AE87AC65');
        $this->addSql('DROP INDEX IDX_E7927C74AE87AC65 ON email');
        $this->addSql('ALTER TABLE email CHANGE send_from_id customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_E7927C749395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E7927C749395C3F3 ON email (customer_id)');
    }
}
