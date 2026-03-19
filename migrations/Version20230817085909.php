<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230817085909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ADD offer_sub_category_id INT DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76E6296DAD FOREIGN KEY (offer_sub_category_id) REFERENCES offer_sub_category (id)');
        $this->addSql('CREATE INDEX IDX_D8698A76E6296DAD ON document (offer_sub_category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76E6296DAD');
        $this->addSql('DROP INDEX IDX_D8698A76E6296DAD ON document');
        $this->addSql('ALTER TABLE document DROP offer_sub_category_id, DROP description');
    }
}
