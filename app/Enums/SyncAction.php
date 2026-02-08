<?php

namespace App\Enums;

enum SyncAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
}
