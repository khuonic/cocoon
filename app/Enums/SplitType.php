<?php

namespace App\Enums;

enum SplitType: string
{
    case Equal = 'equal';
    case FullPayer = 'full_payer';
    case FullOther = 'full_other';
    case Custom = 'custom';
}
