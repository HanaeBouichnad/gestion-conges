<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute photo_url, actif et derniere_connexion_at sur la table user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD photo_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD actif BOOLEAN NOT NULL DEFAULT true');
        $this->addSql('ALTER TABLE "user" ALTER actif DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ADD derniere_connexion_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP photo_url');
        $this->addSql('ALTER TABLE "user" DROP actif');
        $this->addSql('ALTER TABLE "user" DROP derniere_connexion_at');
    }
}
