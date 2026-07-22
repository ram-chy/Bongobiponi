<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case OPENING = 'opening';
    case PURCHASE = 'purchase';
    case PURCHASE_RETURN = 'purchase_return';
    case SALE = 'sale';
    case SALE_RETURN = 'sale_return';
    case ADJUSTMENT = 'adjustment';
    case DAMAGE = 'damage';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';

    public function label(): string
    {
        return match ($this) {
            self::OPENING => 'Opening Stock',
            self::PURCHASE => 'Purchase',
            self::PURCHASE_RETURN => 'Purchase Return',
            self::SALE => 'Sale',
            self::SALE_RETURN => 'Sale Return',
            self::ADJUSTMENT => 'Adjustment',
            self::DAMAGE => 'Damage',
            self::TRANSFER_IN => 'Transfer In',
            self::TRANSFER_OUT => 'Transfer Out',
        };
    }
}
