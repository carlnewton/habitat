<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129104410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE moderation_log DROP FOREIGN KEY FK_7AE8684DA76ED395');
        $this->addSql('ALTER TABLE moderation_log CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE moderation_log ADD CONSTRAINT FK_7AE8684DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE moderation_log DROP FOREIGN KEY FK_7AE8684DA76ED395');
        $this->addSql('ALTER TABLE moderation_log CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE moderation_log ADD CONSTRAINT FK_7AE8684DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
