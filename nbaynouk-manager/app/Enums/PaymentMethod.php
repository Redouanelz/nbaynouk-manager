<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Cheque = 'cheque';
    case Card = 'card';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Virement bancaire',
            self::Cash => 'Espèces',
            self::Cheque => 'Chèque',
            self::Card => 'Carte',
            self::Other => 'Autre',
        };
    }
}
