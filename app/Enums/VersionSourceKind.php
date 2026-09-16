<?php

namespace App\Enums;

enum VersionSourceKind: string
{
    case RELEASE = 'release';
    case TAG = 'tag';
}
