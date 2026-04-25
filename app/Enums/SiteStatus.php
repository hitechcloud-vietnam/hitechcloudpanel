<?php

namespace App\Enums;

use App\Contracts\HiTechCloudPanelEnum;

enum SiteStatus: string implements HiTechCloudPanelEnum
{
    case READY = 'ready';
    case INSTALLING = 'installing';
    case INSTALLATION_FAILED = 'installation_failed';
    case DELETING = 'deleting';

    public function getColor(): string
    {
        return match ($this) {
            self::READY => 'success',
            self::INSTALLING => 'warning',
            self::INSTALLATION_FAILED,
            self::DELETING => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
