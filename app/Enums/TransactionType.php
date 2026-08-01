<?php

namespace App\Enums;

enum TransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case Subsidy = 'subsidy';
    case Refund = 'refund';
    case Hold = 'hold';
    case Capture = 'capture';
    case TopUp = 'top_up';
    case Earned = 'earned';
    case P2pDebit = 'p2p_debit';
    case P2pCredit = 'p2p_credit';
    case Fee = 'fee';
    case Payout = 'payout';

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Credit',
            self::Debit => 'Debit',
            self::Subsidy => 'Subsidy Credit',
            self::Refund => 'Refund',
            self::Hold => 'Hold',
            self::Capture => 'Capture',
            self::TopUp => 'Wallet Top-up',
            self::Earned => 'Earnings',
            self::P2pDebit => 'Transfer Sent',
            self::P2pCredit => 'Transfer Received',
            self::Fee => 'Platform Fee',
            self::Payout => 'Withdrawal',
        };
    }
}
