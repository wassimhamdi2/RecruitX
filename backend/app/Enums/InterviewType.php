<?php

namespace App\Enums;

enum InterviewType: string
{
    case PHONE = 'phone';
    case VIDEO = 'video';
    case ONSITE = 'onsite';
    case TECHNICAL = 'technical';
    case HR = 'hr';
    case FINAL = 'final';
}