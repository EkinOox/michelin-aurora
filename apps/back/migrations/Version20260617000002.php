<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notifications table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE notifications (
            id UUID NOT NULL,
            user_id UUID NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            data JSON DEFAULT NULL,
            read BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        )");
        $this->addSql("CREATE INDEX idx_notif_user ON notifications (user_id)");
        $this->addSql("COMMENT ON COLUMN notifications.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN notifications.user_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN notifications.created_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notifications');
    }
}
