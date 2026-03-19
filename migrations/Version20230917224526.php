<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230917224526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inquiry DROP FOREIGN KEY FK_5A3903F053C674EE');
        $this->addSql('DROP INDEX UNIQ_5A3903F053C674EE ON inquiry');
        $this->addSql('ALTER TABLE inquiry DROP offer_id');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873EA7C41D6F');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E8D9F6D38');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873EA7C41D6F FOREIGN KEY (option_id) REFERENCES offer_option (id)');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873E8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE offer_option DROP FOREIGN KEY FK_83D3771153C674EE');
        $this->addSql('DROP INDEX UNIQ_83D3771153C674EE ON offer_option');
        $this->addSql('ALTER TABLE offer_option DROP offer_id');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939853C674EE');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939853C674EE FOREIGN KEY (offer_id) REFERENCES offer (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offer_option ADD offer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE offer_option ADD CONSTRAINT FK_83D3771153C674EE FOREIGN KEY (offer_id) REFERENCES offer (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_83D3771153C674EE ON offer_option (offer_id)');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939853C674EE');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939853C674EE FOREIGN KEY (offer_id) REFERENCES offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inquiry ADD offer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE inquiry ADD CONSTRAINT FK_5A3903F053C674EE FOREIGN KEY (offer_id) REFERENCES offer (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A3903F053C674EE ON inquiry (offer_id)');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873EA7C41D6F');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E8D9F6D38');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873EA7C41D6F FOREIGN KEY (option_id) REFERENCES offer_option (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873E8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE SET NULL');
    }
}
