<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Enums;

enum StampSource: string
{
    case StaffTerminal = 'staff_terminal';
    case Receipt = 'receipt';
    case TillQr = 'till_qr';
    case Api = 'api';
    case Manual = 'manual';
}
