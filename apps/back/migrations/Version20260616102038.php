<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616102038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE community_riders (id UUID NOT NULL, name VARCHAR(255) NOT NULL, initials VARCHAR(4) NOT NULL, rank VARCHAR(255) NOT NULL, km_this_month DOUBLE PRECISION NOT NULL, match_percent INT NOT NULL, color_hex VARCHAR(16) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE events (id UUID NOT NULL, name VARCHAR(255) NOT NULL, date VARCHAR(32) NOT NULL, place VARCHAR(255) NOT NULL, distance_label VARCHAR(32) NOT NULL, type VARCHAR(255) NOT NULL, km_away DOUBLE PRECISION NOT NULL, riders INT NOT NULL, image_key VARCHAR(64) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE news_articles (id UUID NOT NULL, tag VARCHAR(64) NOT NULL, title VARCHAR(255) NOT NULL, date VARCHAR(32) NOT NULL, read_time VARCHAR(16) NOT NULL, image_key VARCHAR(64) NOT NULL, body TEXT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE retailers (id UUID NOT NULL, name VARCHAR(255) NOT NULL, sub VARCHAR(255) NOT NULL, url VARCHAR(512) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE reward_catalog_items (id UUID NOT NULL, cost INT NOT NULL, title VARCHAR(255) NOT NULL, sub VARCHAR(255) NOT NULL, icon VARCHAR(32) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE tires ADD image_key VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE tires ADD subtitle VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE tires ADD tag VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE tires ADD color_token VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE community_riders');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE news_articles');
        $this->addSql('DROP TABLE retailers');
        $this->addSql('DROP TABLE reward_catalog_items');
        $this->addSql('ALTER TABLE tires DROP image_key');
        $this->addSql('ALTER TABLE tires DROP subtitle');
        $this->addSql('ALTER TABLE tires DROP tag');
        $this->addSql('ALTER TABLE tires DROP color_token');
    }
}
