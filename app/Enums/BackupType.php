<?php

namespace App\Enums;

use App\Contracts\HiTechCloudPanelEnum;

enum BackupType: string implements HiTechCloudPanelEnum
{
    case DATABASE = 'database';
    case FILE = 'file';

    public function getColor(): string
    {
        return 'default';
    }

    public function getText(): string
    {
        return $this->value;
    }
}
