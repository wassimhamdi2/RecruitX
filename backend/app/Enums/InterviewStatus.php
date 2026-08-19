<?php

namespace App\Enums;

enum InterviewStatus: string
{
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case RESCHEDULED = 'rescheduled';
    case NO_SHOW = 'no_show';
}