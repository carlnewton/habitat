<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250413083737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP removed, DROP removed_datetime');
        $this->addSql('ALTER TABLE post DROP removed, DROP removed_datetime');
        $this->addSql('ALTER TABLE user DROP suspended, DROP suspended_datetime');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post ADD removed TINYINT(1) NOT NULL, ADD removed_datetime DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD suspended TINYINT(1) NOT NULL, ADD suspended_datetime DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE comment ADD removed TINYINT(1) NOT NULL, ADD removed_datetime DATETIME DEFAULT NULL');
    }
}
