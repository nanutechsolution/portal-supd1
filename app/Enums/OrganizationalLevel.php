<?php

namespace App\Enums;

enum OrganizationalLevel: string
{
    case Pimpinan = 'pimpinan';
    case KepalaBagian = 'kepala_bagian';
    case Fungsional = 'fungsional';
    case Kasubbag = 'kasubbag';
    case Kapokja = 'kapokja';
    case Katim = 'katim';

    public function label(): string
    {
        return match ($this) {
            self::Pimpinan => 'Pimpinan',
            self::KepalaBagian => 'Kepala Bagian',
            self::Fungsional => 'Jabatan Fungsional',
            self::Kasubbag => 'Kepala Subbagian',
            self::Kapokja => 'Kepala Kelompok Kerja',
            self::Katim => 'Ketua Tim',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pimpinan => 'primary',
            self::KepalaBagian => 'info',
            self::Fungsional => 'gray',
            self::Kasubbag => 'danger',
            self::Kapokja => 'warning',
            self::Katim => 'success',
        };
    }

    public function cardVariant(): string
    {
        return match ($this) {
            self::Pimpinan => 'leader',
            self::KepalaBagian => 'section',
            self::Fungsional => 'functional',
            self::Kasubbag => 'subsection',
            self::Kapokja => 'work-group',
            self::Katim => 'team',
        };
    }

    public function hasPhoto(): bool
    {
        return $this !== self::Fungsional;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $level): array => [$level->value => $level->label()])
            ->all();
    }
}
