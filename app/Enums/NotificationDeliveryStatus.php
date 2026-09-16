<?php

namespace App\Enums;

enum NotificationDeliveryStatus: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case FAILED = 'failed';
}
