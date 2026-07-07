<?php

namespace App\Enum;

enum StatutDemande: string
{
    case BROUILLON = 'brouillon';
    case EN_ATTENTE = 'en_attente';
    case APPROUVE = 'approuve';
    case REFUSE = 'refuse';
    case ANNULE = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::EN_ATTENTE => "En attente d'approbation",
            self::APPROUVE => 'Approuvé',
            self::REFUSE => 'Refusé',
            self::ANNULE => 'Annulé',
        };
    }
}