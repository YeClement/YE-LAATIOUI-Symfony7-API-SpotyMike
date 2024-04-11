<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240411135454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist DROP FOREIGN KEY FK_1599687F0BE9073');
        $this->addSql('ALTER TABLE artist DROP FOREIGN KEY FK_1599687DE94BC09');
        $this->addSql('DROP INDEX UNIQ_1599687DE94BC09 ON artist');
        $this->addSql('DROP INDEX IDX_1599687F0BE9073 ON artist');
        $this->addSql('ALTER TABLE artist DROP user_id_user_id, DROP artistlabel_id');
        $this->addSql('ALTER TABLE artist ADD CONSTRAINT FK_1599687BF396750 FOREIGN KEY (id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist DROP FOREIGN KEY FK_1599687BF396750');
        $this->addSql('ALTER TABLE artist ADD user_id_user_id INT NOT NULL, ADD artistlabel_id INT NOT NULL');
        $this->addSql('ALTER TABLE artist ADD CONSTRAINT FK_1599687F0BE9073 FOREIGN KEY (artistlabel_id) REFERENCES label (id)');
        $this->addSql('ALTER TABLE artist ADD CONSTRAINT FK_1599687DE94BC09 FOREIGN KEY (user_id_user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1599687DE94BC09 ON artist (user_id_user_id)');
        $this->addSql('CREATE INDEX IDX_1599687F0BE9073 ON artist (artistlabel_id)');
        $this->addSql('ALTER TABLE user CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
