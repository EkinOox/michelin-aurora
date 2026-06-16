<?php

namespace App\Entity\Enum;

enum TerrainType: string
{
    case Road = 'road';
    case Forest = 'forest';
    case Gravel = 'gravel';
    case Mixed = 'mixed';
    case Mountain = 'mountain';
}
