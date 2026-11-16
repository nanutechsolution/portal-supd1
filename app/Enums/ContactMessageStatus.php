<?php

namespace App\Enums;

enum ContactMessageStatus: string
{
    case Baru = 'baru';
    case Dibaca = 'dibaca';
    case Dibalas = 'dibalas';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Dibaca => 'Sudah dibaca',
            self::Dibalas => 'Sudah dibalas',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Baru => 'warning',
            self::Dibaca => 'info',
            self::Dibalas => 'success',
        };
    }
}
