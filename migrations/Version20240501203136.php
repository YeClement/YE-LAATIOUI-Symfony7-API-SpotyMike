<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240501203136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist ADD follower_id INT DEFAULT NULL, ADD avatar VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE artist ADD CONSTRAINT FK_1599687AC24F853 FOREIGN KEY (follower_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_1599687AC24F853 ON artist (follower_id)');
        $this->addSql('ALTER TABLE song ADD featured_artist_id INT DEFAULT NULL, ADD featuring TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE song ADD CONSTRAINT FK_33EDEEA1EFF78C15 FOREIGN KEY (featured_artist_id) REFERENCES artist (id)');
        $this->addSql('CREATE INDEX IDX_33EDEEA1EFF78C15 ON song (featured_artist_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist DROP FOREIGN KEY FK_1599687AC24F853');
        $this->addSql('DROP INDEX IDX_1599687AC24F853 ON artist');
        $this->addSql('ALTER TABLE artist DROP follower_id, DROP avatar, DROP created_at');
        $this->addSql('ALTER TABLE song DROP FOREIGN KEY FK_33EDEEA1EFF78C15');
        $this->addSql('DROP INDEX IDX_33EDEEA1EFF78C15 ON song');
        $this->addSql('ALTER TABLE song DROP featured_artist_id, DROP featuring');
    }
}
