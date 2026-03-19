<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250319172316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE push_subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, endpoint VARCHAR(255) NOT NULL, `keys` JSON NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_562830F3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE push_subscription ADD CONSTRAINT FK_562830F3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE push_subscription MODIFY COLUMN keys JSON;');
//        $this->addSql('ALTER TABLE customer CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE inquiry CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE invoice CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_answers CHANGE dependencies dependencies JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_option CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_sub_category CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE reminder CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE settings CHANGE app_active_log app_active_log JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE data data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user DROP push_token');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE push_subscription DROP FOREIGN KEY FK_562830F3A76ED395');
        $this->addSql('DROP TABLE push_subscription');
        $this->addSql('ALTER TABLE push_subscription MODIFY COLUMN keys JSON;');
//        $this->addSql('ALTER TABLE reminder CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE settings CHANGE app_active_log app_active_log JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE data data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_option CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE inquiry CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_sub_category CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE customer CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE offer_answers CHANGE dependencies dependencies JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
//        $this->addSql('ALTER TABLE invoice CHANGE context context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user ADD push_token VARCHAR(255) DEFAULT NULL');
    }
}
