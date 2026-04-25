<?php

namespace App\Enums;

use App\Contracts\HiTechCloudPanelEnum;

enum DeploymentStatus: string implements HiTechCloudPanelEnum
{
    case DEPLOYING = 'deploying';
    case FINISHED = 'finished';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::DEPLOYING => 'warning',
            self::FINISHED => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
