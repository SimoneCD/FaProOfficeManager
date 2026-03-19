<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241130183948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE index_states (id INT AUTO_INCREMENT NOT NULL, document_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, send_cost_estimate TINYINT(1) NOT NULL, send_offer TINYINT(1) NOT NULL, send_part_invoice TINYINT(1) NOT NULL, send_invoice TINYINT(1) NOT NULL, action_first VARCHAR(255) NOT NULL, action_last VARCHAR(255) DEFAULT NULL, help LONGTEXT DEFAULT NULL, auto_move_by_time TINYINT(1) DEFAULT NULL, INDEX IDX_285C88BCC33F7837 (document_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE index_states ADD CONSTRAINT FK_285C88BCC33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE action_log ADD answer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE action_log ADD CONSTRAINT FK_B2C5F685AA334807 FOREIGN KEY (answer_id) REFERENCES action_log (id)');
        $this->addSql('CREATE INDEX IDX_B2C5F685AA334807 ON action_log (answer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE index_states DROP FOREIGN KEY FK_285C88BCC33F7837');
        $this->addSql('DROP TABLE index_states');
        $this->addSql('ALTER TABLE action_log DROP FOREIGN KEY FK_B2C5F685AA334807');
        $this->addSql('DROP INDEX IDX_B2C5F685AA334807 ON action_log');
        $this->addSql('ALTER TABLE action_log DROP answer_id');
    }
}
