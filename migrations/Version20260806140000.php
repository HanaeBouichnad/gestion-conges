<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute role_souhaite sur la table user (inscription en libre-service)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD role_souhaite VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP role_souhaite');
    }
}
