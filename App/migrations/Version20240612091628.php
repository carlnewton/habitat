<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240612091628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post_attachment ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE post_attachment ADD CONSTRAINT FK_5A27D07AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_5A27D07AA76ED395 ON post_attachment (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post_attachment DROP FOREIGN KEY FK_5A27D07AA76ED395');
        $this->addSql('DROP INDEX IDX_5A27D07AA76ED395 ON post_attachment');
        $this->addSql('ALTER TABLE post_attachment DROP user_id');
    }
}
