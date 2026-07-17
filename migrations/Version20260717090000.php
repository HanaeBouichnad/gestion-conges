<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne description au type de congé et pré-remplit les types existants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE type_conge ADD description TEXT DEFAULT NULL');
        $this->addSql("UPDATE type_conge SET description = 'Congé payé accordé chaque année civile.' WHERE libelle = 'Congé annuel'");
        $this->addSql("UPDATE type_conge SET description = 'Congé accordé en cas de maladie, sur présentation d''un justificatif médical.' WHERE libelle = 'Congé maladie'");
        $this->addSql("UPDATE type_conge SET description = 'Congé non rémunéré, accordé sans limite de solde.' WHERE libelle = 'Congé sans solde'");
        $this->addSql("UPDATE type_conge SET description = 'Jours accordés en compensation d''heures ou de jours supplémentaires travaillés.' WHERE libelle = 'Récupération'");
        $this->addSql("UPDATE type_conge SET description = 'Congé accordé pour un événement familial ou personnel exceptionnel.' WHERE libelle = 'Congé exceptionnel'");
        $this->addSql("UPDATE type_conge SET description = '' WHERE description IS NULL");
        $this->addSql('ALTER TABLE type_conge ALTER description SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE type_conge DROP description');
    }
}
