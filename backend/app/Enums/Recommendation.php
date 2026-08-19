<?php

namespace App\Enums;

enum Recommendation: string
{
    case STRONG_YES = 'strong_yes';
    case YES = 'yes';
    case MAYBE = 'maybe';
    case NO = 'no';
    case STRONG_NO = 'strong_no';
}