<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616102142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes ADD distance_km DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE routes ADD elevation_gain_m INT NOT NULL');
        $this->addSql('ALTER TABLE routes ADD surface VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE routes ADD duration_label VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE routes ADD description TEXT NOT NULL');
        $this->addSql('ALTER TABLE routes ADD safety_score INT NOT NULL');
        $this->addSql('ALTER TABLE routes ADD fun_score INT NOT NULL');
        $this->addSql('ALTER TABLE routes ADD match_score INT NOT NULL');
        $this->addSql('ALTER TABLE routes ADD tag VARCHAR(64) NOT NULL');
        $this->addSql("ALTER TABLE users ADD name VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE users ADD city VARCHAR(255) NOT NULL DEFAULT ''");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routes DROP distance_km');
        $this->addSql('ALTER TABLE routes DROP elevation_gain_m');
        $this->addSql('ALTER TABLE routes DROP surface');
        $this->addSql('ALTER TABLE routes DROP duration_label');
        $this->addSql('ALTER TABLE routes DROP description');
        $this->addSql('ALTER TABLE routes DROP safety_score');
        $this->addSql('ALTER TABLE routes DROP fun_score');
        $this->addSql('ALTER TABLE routes DROP match_score');
        $this->addSql('ALTER TABLE routes DROP tag');
        $this->addSql('ALTER TABLE users DROP name');
        $this->addSql('ALTER TABLE users DROP city');
    }
}
