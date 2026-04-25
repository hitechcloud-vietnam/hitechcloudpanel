<?php

namespace App\Enums;

use App\Contracts\HiTechCloudPanelEnum;
use App\Traits\HasEnumHelpers;

enum LoadBalancerMethod: string implements HiTechCloudPanelEnum
{
    use HasEnumHelpers;

    case ROUND_ROBIN = 'round-robin';
    case LEAST_CONNECTIONS = 'least-connections';
    case IP_HASH = 'ip-hash';

    public function getColor(): string
    {
        return 'default';
    }

    public function getText(): string
    {
        return $this->value;
    }
}
