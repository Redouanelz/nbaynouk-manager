<?php

namespace App\Enums;

enum ProjectHealth: string
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Watch = 'watch';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Excellent => 'Excellent', self::Good => 'Bon', self::Watch => 'À surveiller', self::Critical => 'Critique',
        };
    }
}
