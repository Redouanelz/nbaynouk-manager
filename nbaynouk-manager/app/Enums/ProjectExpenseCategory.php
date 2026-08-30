<?php

namespace App\Enums;

enum ProjectExpenseCategory: string
{
    case Production = 'production';
    case Location = 'location';
    case Model = 'model';
    case Transport = 'transport';
    case Equipment = 'equipment';
    case Freelance = 'freelance';
    case Design = 'design';
    case Advertising = 'advertising';
    case Food = 'food';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Production => 'Production', self::Location => 'Location', self::Model => 'Modèle / Casting',
            self::Transport => 'Transport', self::Equipment => 'Matériel', self::Freelance => 'Freelance',
            self::Design => 'Design', self::Advertising => 'Publicité', self::Food => 'Restauration', self::Other => 'Autre',
        };
    }
}
