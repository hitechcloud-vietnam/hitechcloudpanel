<?php

namespace App\Enums;

use App\Contracts\HiTechCloudPanelEnum;

enum FirewallRuleStatus: string implements HiTechCloudPanelEnum
{
    case CREATING = 'creating';
    case UPDATING = 'updating';
    case READY = 'ready';
    case DELETING = 'deleting';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::CREATING => 'info',
            self::UPDATING => 'warning',
            self::DELETING,
            self::FAILED => 'danger',
            self::READY => 'success',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
