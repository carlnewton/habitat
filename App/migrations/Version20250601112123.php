<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250601112123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_freeze_log (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, freeze_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', unfreeze_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', reason VARCHAR(255) NOT NULL, INDEX IDX_1FBF84DAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_freeze_log ADD CONSTRAINT FK_1FBF84DAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_freeze_log DROP FOREIGN KEY FK_1FBF84DAA76ED395');
        $this->addSql('DROP TABLE user_freeze_log');
    }
}
