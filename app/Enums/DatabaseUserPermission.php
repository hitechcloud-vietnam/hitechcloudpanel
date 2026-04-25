<?php

namespace App\Enums;

use App\Contracts\HiTechCloudPanelEnum;

enum DatabaseUserPermission: string implements HiTechCloudPanelEnum
{
    case READ = 'read';
    case WRITE = 'write';
    case ADMIN = 'admin';

    public function getColor(): string
    {
        return match ($this) {
            self::READ => 'default',
            self::WRITE => 'info',
            self::ADMIN => 'warning',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
