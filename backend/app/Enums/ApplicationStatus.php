<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case APPLIED = 'applied';
    case SCREENING = 'screening';
    case SHORTLISTED = 'shortlisted';
    case INTERVIEW = 'interview';
    case EVALUATION = 'evaluation';
    case OFFER = 'offer';
    case HIRED = 'hired';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';
}