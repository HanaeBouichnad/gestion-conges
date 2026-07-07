<?php

namespace App\Enum;

enum PeriodeJournee: string
{
    case JOURNEE_COMPLETE = 'journee_complete';
    case MATIN = 'matin';
    case APRES_MIDI = 'apres_midi';
}