<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241116174643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_log ADD customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE action_log ADD CONSTRAINT FK_B2C5F6859395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('CREATE INDEX IDX_B2C5F6859395C3F3 ON action_log (customer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_log DROP FOREIGN KEY FK_B2C5F6859395C3F3');
        $this->addSql('DROP INDEX IDX_B2C5F6859395C3F3 ON action_log');
        $this->addSql('ALTER TABLE action_log DROP customer_id');
    }
}
