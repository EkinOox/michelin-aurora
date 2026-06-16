<?php

namespace App\Entity\Enum;

enum BikeType: string
{
    case Route = 'route';
    case Vtt = 'vtt';
    case Gravel = 'gravel';
    case Vae = 'vae';
}
