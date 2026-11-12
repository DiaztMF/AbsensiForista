<?php

namespace App\Enums;

enum AttemptResult: string
{
    case Matched = 'matched';
    case OutsideRadius = 'outside_radius';
    case PoorAccuracy = 'poor_accuracy';
}
