<?php

namespace App\Enums;

use App\Contracts\HiTechCloudPanelEnum;

enum WorkflowRunStatus: string implements HiTechCloudPanelEnum
{
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function getColor(): string
    {
        return match ($this) {
            self::RUNNING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
