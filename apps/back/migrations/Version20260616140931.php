<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616140931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD password VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE users ADD roles JSON NOT NULL');
        $this->addSql('ALTER TABLE users ALTER name DROP DEFAULT');
        $this->addSql('ALTER TABLE users ALTER city DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP password');
        $this->addSql('ALTER TABLE users DROP roles');
        $this->addSql('ALTER TABLE users ALTER name SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE users ALTER city SET DEFAULT \'\'');
    }
}
