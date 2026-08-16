<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Intake = 'intake';
    case ToProcure = 'to_procure';
    case ToPack = 'to_pack';
    case Packed = 'packed';
    case Dispatched = 'dispatched';
    case Delivered = 'delivered';
    case Rto = 'rto';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Intake => 'Intake',
            self::ToProcure => 'To Procure',
            self::ToPack => 'To Pack',
            self::Packed => 'Packed',
            self::Dispatched => 'Dispatched',
            self::Delivered => 'Delivered',
            self::Rto => 'RTO',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::Delivered, self::Rto, self::Cancelled => true,
            default => false,
        };
    }
}
