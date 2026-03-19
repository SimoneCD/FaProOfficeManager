<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241114155024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE grok DROP FOREIGN KEY FK_E8C563208C03F15C');
        $this->addSql('DROP INDEX IDX_E8C563208C03F15C ON grok');
        $this->addSql('ALTER TABLE grok ADD answer LONGTEXT NOT NULL, DROP employee_id, CHANGE text question LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE project_team ADD `default` TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE grok ADD employee_id INT DEFAULT NULL, ADD text LONGTEXT NOT NULL, DROP question, DROP answer');
        $this->addSql('ALTER TABLE grok ADD CONSTRAINT FK_E8C563208C03F15C FOREIGN KEY (employee_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E8C563208C03F15C ON grok (employee_id)');
        $this->addSql('ALTER TABLE project_team DROP `default`');
    }
}
