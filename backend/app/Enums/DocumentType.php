<?php

namespace App\Enums;

enum DocumentType: string
{
    case CV = 'cv';
    case COVER_LETTER = 'cover_letter';
    case CERTIFICATE = 'certificate';
    case OTHER = 'other';
}