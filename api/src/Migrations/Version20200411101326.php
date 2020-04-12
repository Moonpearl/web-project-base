<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200411101326 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE example_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE example ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE example ADD CONSTRAINT FK_6EEC9B9F12469DE2 FOREIGN KEY (category_id) REFERENCES example_category (id)');
        $this->addSql('CREATE INDEX IDX_6EEC9B9F12469DE2 ON example (category_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE example DROP FOREIGN KEY FK_6EEC9B9F12469DE2');
        $this->addSql('DROP TABLE example_category');
        $this->addSql('DROP INDEX IDX_6EEC9B9F12469DE2 ON example');
        $this->addSql('ALTER TABLE example DROP category_id');
    }
}
