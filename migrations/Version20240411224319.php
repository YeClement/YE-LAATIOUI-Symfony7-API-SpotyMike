<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240411224319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album ADD artist_user_id_user_id INT DEFAULT NULL, ADD id_album VARCHAR(90) NOT NULL, DROP label, DROP created_at');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E437E9F183A FOREIGN KEY (artist_user_id_user_id) REFERENCES artist (id)');
        $this->addSql('CREATE INDEX IDX_39986E437E9F183A ON album (artist_user_id_user_id)');
        $this->addSql('ALTER TABLE song ADD album_id INT DEFAULT NULL, ADD playlist_has_song_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song ADD CONSTRAINT FK_33EDEEA11137ABCF FOREIGN KEY (album_id) REFERENCES album (id)');
        $this->addSql('ALTER TABLE song ADD CONSTRAINT FK_33EDEEA1E2815C07 FOREIGN KEY (playlist_has_song_id) REFERENCES playlist_has_song (id)');
        $this->addSql('CREATE INDEX IDX_33EDEEA11137ABCF ON song (album_id)');
        $this->addSql('CREATE INDEX IDX_33EDEEA1E2815C07 ON song (playlist_has_song_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album DROP FOREIGN KEY FK_39986E437E9F183A');
        $this->addSql('DROP INDEX IDX_39986E437E9F183A ON album');
        $this->addSql('ALTER TABLE album ADD label VARCHAR(33) NOT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP artist_user_id_user_id, DROP id_album');
        $this->addSql('ALTER TABLE song DROP FOREIGN KEY FK_33EDEEA11137ABCF');
        $this->addSql('ALTER TABLE song DROP FOREIGN KEY FK_33EDEEA1E2815C07');
        $this->addSql('DROP INDEX IDX_33EDEEA11137ABCF ON song');
        $this->addSql('DROP INDEX IDX_33EDEEA1E2815C07 ON song');
        $this->addSql('ALTER TABLE song DROP album_id, DROP playlist_has_song_id');
    }
}
