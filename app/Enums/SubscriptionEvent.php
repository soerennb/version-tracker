<?php

namespace App\Enums;

enum SubscriptionEvent: string
{
    case ALL = 'all';
    case RELEASE = 'release';
    case SECURITY = 'security';
    case EOL = 'eol';
}
