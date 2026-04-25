<?php

namespace App\Enums;

use App\Contracts\HiTechCloudPanelEnum;

enum ScriptExecutionStatus: string implements HiTechCloudPanelEnum
{
    case EXECUTING = 'executing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::EXECUTING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
