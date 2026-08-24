<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Lead = 'lead';
    case Waiting = 'waiting';
    case Onboarding = 'onboarding';
    case Launch = 'launch';
    case Suivi = 'suivi';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Prospect',
            self::Waiting => 'En attente',
            self::Onboarding => 'Onboarding',
            self::Launch => 'Lancement',
            self::Suivi => 'Suivi',
            self::Paused => 'En pause',
            self::Completed => 'Terminé',
            self::Cancelled => 'Annulé',
        };
    }
}
