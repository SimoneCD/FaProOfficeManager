<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240125200021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_E7927C7459574F23');
        $this->addSql('ALTER TABLE email ADD customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_E7927C749395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_E7927C7459574F23 FOREIGN KEY (send_to_id) REFERENCES customer (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E7927C749395C3F3 ON email (customer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_E7927C749395C3F3');
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_E7927C7459574F23');
        $this->addSql('DROP INDEX IDX_E7927C749395C3F3 ON email');
        $this->addSql('ALTER TABLE email DROP customer_id');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_E7927C7459574F23 FOREIGN KEY (send_to_id) REFERENCES customer (id) ON DELETE CASCADE');
    }
}
