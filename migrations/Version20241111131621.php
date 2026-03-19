<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241111131621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE grok (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, employee_id INT DEFAULT NULL, text LONGTEXT NOT NULL, date DATETIME NOT NULL, INDEX IDX_E8C56320A76ED395 (user_id), INDEX IDX_E8C563208C03F15C (employee_id), INDEX date_idx (date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        #$this->addSql('CREATE TABLE index_states (id INT AUTO_INCREMENT NOT NULL, document_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, send_cost_estimate TINYINT(1) NOT NULL, send_offer TINYINT(1) NOT NULL, send_part_invoice TINYINT(1) NOT NULL, send_invoice TINYINT(1) NOT NULL, action_first VARCHAR(255) NOT NULL, action_last VARCHAR(255) DEFAULT NULL, help LONGTEXT DEFAULT NULL, auto_move_by_time TINYINT(1) DEFAULT NULL, INDEX IDX_285C88BCC33F7837 (document_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE grok ADD CONSTRAINT FK_E8C56320A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE grok ADD CONSTRAINT FK_E8C563208C03F15C FOREIGN KEY (employee_id) REFERENCES user (id) ON DELETE SET NULL');
        #$this->addSql('ALTER TABLE index_states ADD CONSTRAINT FK_285C88BCC33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        #$this->addSql('ALTER TABLE customer CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        #$this->addSql('ALTER TABLE inquiry CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
       # $this->addSql('ALTER TABLE invoice CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        #$this->addSql('ALTER TABLE offer CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        #$this->addSql('ALTER TABLE offer_answers CHANGE dependencies dependencies JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        #$this->addSql('ALTER TABLE offer_option CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        #$this->addSql('ALTER TABLE offer_sub_category CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        #$this->addSql('ALTER TABLE reminder CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        #$this->addSql('ALTER TABLE settings CHANGE app_active_log app_active_log JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE data data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        #$this->addSql('ALTER TABLE user CHANGE roles roles JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE grok DROP FOREIGN KEY FK_E8C56320A76ED395');
        $this->addSql('ALTER TABLE grok DROP FOREIGN KEY FK_E8C563208C03F15C');
       # $this->addSql('ALTER TABLE index_states DROP FOREIGN KEY FK_285C88BCC33F7837');
        $this->addSql('DROP TABLE grok');
//        $this->addSql('DROP TABLE index_states');
//        $this->addSql('ALTER TABLE reminder CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE settings CHANGE app_active_log app_active_log JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE data data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_option CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE inquiry CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_sub_category CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE customer CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_answers CHANGE dependencies dependencies JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE invoice CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE user CHANGE roles roles JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }
}
