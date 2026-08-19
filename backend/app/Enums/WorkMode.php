<?php

namespace App\Enums;

enum WorkMode: string
{
    case ON_SITE = 'on_site';
    case REMOTE = 'remote';
    case HYBRID = 'hybrid';
}