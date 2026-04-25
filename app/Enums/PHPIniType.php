<?php

namespace App\Enums;

use App\Contracts\HiTechCloudPanelEnum;

enum PHPIniType: string implements HiTechCloudPanelEnum
{
    case CLI = 'cli';
    case FPM = 'fpm';

    public function getColor(): string
    {
        return 'default';
    }

    public function getText(): string
    {
        return $this->value;
    }
}
