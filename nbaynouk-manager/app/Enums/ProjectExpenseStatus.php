<?php

namespace App\Enums;

enum ProjectExpenseStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'À payer',
            self::Paid => 'Payée',
        };
    }

    public function badgeClass(): string
    {
        return $this === self::Paid ? 'badge-positive' : 'badge-warning';
    }
}
