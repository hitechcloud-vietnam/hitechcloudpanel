<?php

namespace App\Enums;

use App\Contracts\HiTechCloudPanelEnum;

enum SslStatus: string implements HiTechCloudPanelEnum
{
    case CREATED = 'created';
    case CREATING = 'creating';
    case DELETING = 'deleting';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::CREATED => 'success',
            self::CREATING,
            self::DELETING => 'warning',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
