<?php

namespace App\Enums;

enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAW = 'withdraw';
    case ESCROW_HOLD = 'escrow_hold';
    case ESCROW_RELEASE = 'escrow_release';
    case REFUND = 'refund';
    case TRANSFER = 'transfer';
}
