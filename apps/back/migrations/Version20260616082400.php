<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616082400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cyclist_profiles (id UUID NOT NULL, bike_type VARCHAR(255) NOT NULL, rider_level VARCHAR(255) NOT NULL, usage_type VARCHAR(255) NOT NULL, preferences JSON DEFAULT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8689E5C3A76ED395 ON cyclist_profiles (user_id)');
        $this->addSql('CREATE TABLE rewards_codes (id UUID NOT NULL, code VARCHAR(32) NOT NULL, discount_eur NUMERIC(6, 2) NOT NULL, used BOOLEAN NOT NULL, generated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_532BFBD77153098 ON rewards_codes (code)');
        $this->addSql('CREATE INDEX IDX_532BFBDA76ED395 ON rewards_codes (user_id)');
        $this->addSql('CREATE TABLE rides (id UUID NOT NULL, distance_km DOUBLE PRECISION NOT NULL, elevation_m INT NOT NULL, terrain_type VARCHAR(255) NOT NULL, weather JSON DEFAULT NULL, points_earned INT NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9D4620A3A76ED395 ON rides (user_id)');
        $this->addSql('CREATE TABLE routes (id UUID NOT NULL, name VARCHAR(255) NOT NULL, bike_type VARCHAR(255) NOT NULL, difficulty VARCHAR(255) NOT NULL, michelin_score DOUBLE PRECISION NOT NULL, geojson JSON DEFAULT NULL, tire_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_32D5C2B3BC5ADD68 ON routes (tire_id)');
        $this->addSql('CREATE TABLE telemetry_sessions (id UUID NOT NULL, pressure_front_bar DOUBLE PRECISION NOT NULL, pressure_rear_bar DOUBLE PRECISION NOT NULL, speed_kmh DOUBLE PRECISION NOT NULL, alert_triggered BOOLEAN NOT NULL, recorded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ride_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_3C4637AF302A8A70 ON telemetry_sessions (ride_id)');
        $this->addSql('CREATE TABLE tires (id UUID NOT NULL, name VARCHAR(255) NOT NULL, bike_type VARCHAR(255) NOT NULL, scores JSON DEFAULT NULL, price_eur NUMERIC(8, 2) NOT NULL, avg_km_lifetime INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, email VARCHAR(180) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, rewards_level VARCHAR(255) NOT NULL, total_points INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE cyclist_profiles ADD CONSTRAINT FK_8689E5C3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE rewards_codes ADD CONSTRAINT FK_532BFBDA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE rides ADD CONSTRAINT FK_9D4620A3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE routes ADD CONSTRAINT FK_32D5C2B3BC5ADD68 FOREIGN KEY (tire_id) REFERENCES tires (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE telemetry_sessions ADD CONSTRAINT FK_3C4637AF302A8A70 FOREIGN KEY (ride_id) REFERENCES rides (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cyclist_profiles DROP CONSTRAINT FK_8689E5C3A76ED395');
        $this->addSql('ALTER TABLE rewards_codes DROP CONSTRAINT FK_532BFBDA76ED395');
        $this->addSql('ALTER TABLE rides DROP CONSTRAINT FK_9D4620A3A76ED395');
        $this->addSql('ALTER TABLE routes DROP CONSTRAINT FK_32D5C2B3BC5ADD68');
        $this->addSql('ALTER TABLE telemetry_sessions DROP CONSTRAINT FK_3C4637AF302A8A70');
        $this->addSql('DROP TABLE cyclist_profiles');
        $this->addSql('DROP TABLE rewards_codes');
        $this->addSql('DROP TABLE rides');
        $this->addSql('DROP TABLE routes');
        $this->addSql('DROP TABLE telemetry_sessions');
        $this->addSql('DROP TABLE tires');
        $this->addSql('DROP TABLE users');
    }
}
