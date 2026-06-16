<?php

namespace App\Entity\Enum;

enum UsageType: string
{
    case Sport = 'sport';
    case Commute = 'commute';
    case Leisure = 'leisure';
    case Competition = 'competition';
}
