<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add friendships table and bike_photo_url on cyclist_profiles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE friendships (
            id UUID NOT NULL,
            user_from_id UUID NOT NULL,
            user_to_id UUID NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT uniq_friendship UNIQUE (user_from_id, user_to_id)
        )');
        $this->addSql('CREATE INDEX idx_friendship_from ON friendships (user_from_id)');
        $this->addSql('CREATE INDEX idx_friendship_to ON friendships (user_to_id)');
        $this->addSql('ALTER TABLE friendships ADD CONSTRAINT fk_friendship_from FOREIGN KEY (user_from_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friendships ADD CONSTRAINT fk_friendship_to FOREIGN KEY (user_to_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE cyclist_profiles ADD bike_photo_url VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE friendships DROP CONSTRAINT fk_friendship_from');
        $this->addSql('ALTER TABLE friendships DROP CONSTRAINT fk_friendship_to');
        $this->addSql('DROP TABLE friendships');
        $this->addSql('ALTER TABLE cyclist_profiles DROP bike_photo_url');
    }
}
