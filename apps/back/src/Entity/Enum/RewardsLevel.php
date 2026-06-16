<?php

namespace App\Entity\Enum;

enum RewardsLevel: string
{
    case Explorer = 'explorer';
    case Rider = 'rider';
    case Performer = 'performer';
    case EliteCyclist = 'elite_cyclist';
    case MichelinAmbassador = 'michelin_ambassador';
}
