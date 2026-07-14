<?php

namespace App\Enum;

enum OrigineAnnulation: string
{
    case EMPLOYE = 'employe';
    case RH = 'rh';

    public function label(): string
    {
        return match ($this) {
            self::EMPLOYE => 'Annulé',
            self::RH => 'Annulé par RH',
        };
    }
}
