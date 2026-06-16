<?php

namespace App\Entity\Enum;

enum Difficulty: string
{
    case Easy = 'easy';
    case Moderate = 'moderate';
    case Hard = 'hard';
    case Expert = 'expert';
}
