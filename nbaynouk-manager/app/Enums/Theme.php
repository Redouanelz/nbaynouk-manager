<?php

namespace App\Enums;

enum Theme: string
{
    case Light = 'light';
    case DarkGold = 'dark_gold';

    public function label(): string
    {
        return match ($this) {
            self::Light => 'Clair',
            self::DarkGold => 'Noir & Or',
        };
    }

    public function dataAttribute(): string
    {
        return str_replace('_', '-', $this->value);
    }
}
