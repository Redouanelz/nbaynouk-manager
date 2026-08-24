<?php

namespace App\Enums;

enum BillingType: string
{
    case Monthly = 'monthly';
    case OneTime = 'one_time';
    case Performance = 'performance';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Suivi mensuel',
            self::OneTime => 'Projet ponctuel',
            self::Performance => 'Performance',
            self::Custom => 'Personnalisé',
        };
    }
}
