<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add weight_g and diameter_label to tires';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tires ADD weight_g INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tires ADD diameter_label VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tires DROP weight_g');
        $this->addSql('ALTER TABLE tires DROP diameter_label');
    }
}
