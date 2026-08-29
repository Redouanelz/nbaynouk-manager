<?php

namespace App\Enums;

enum CalendarEventColor: string
{
    case Black = 'black';
    case Green = 'green';
    case Blue = 'blue';
    case Red = 'red';
    case Orange = 'orange';
    case Purple = 'purple';
    case Beige = 'beige';

    public function label(): string
    {
        return match ($this) {
            self::Black => 'Noir', self::Green => 'Vert', self::Blue => 'Bleu',
            self::Red => 'Rouge', self::Orange => 'Orange', self::Purple => 'Violet', self::Beige => 'Beige',
        };
    }

    public function cssClasses(): string
    {
        return 'calendar-color-'.$this->value;
    }
}
