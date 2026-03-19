<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250316232800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ADD user_id INT DEFAULT NULL, ADD user_task_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDED5BB1F8C FOREIGN KEY (user_task_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E00CEDDEA76ED395 ON booking (user_id)');
        $this->addSql('CREATE INDEX IDX_E00CEDDED5BB1F8C ON booking (user_task_id)');
//        $this->addSql('ALTER TABLE customer CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE inquiry CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE invoice CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_answers CHANGE dependencies dependencies JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_option CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_sub_category CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE reminder CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE settings CHANGE app_active_log app_active_log JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE data data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE user CHANGE roles roles JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
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
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEA76ED395');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDED5BB1F8C');
        $this->addSql('DROP INDEX IDX_E00CEDDEA76ED395 ON booking');
        $this->addSql('DROP INDEX IDX_E00CEDDED5BB1F8C ON booking');
        $this->addSql('ALTER TABLE booking DROP user_id, DROP user_task_id');
    }
}
