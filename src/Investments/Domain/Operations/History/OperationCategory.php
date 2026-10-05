<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\History;

use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\Journal\ManualOperationType;

/**
 * What an operation of the history is about, to filter the history by.
 */
enum OperationCategory: string
{
    case Trades = 'trades';
    case Payouts = 'payouts';
    /** Deposits, withdrawals and cash adjustments. */
    case Money = 'money';
    case Fees = 'fees';
    /** Blocks of lots and of cash. */
    case Blocks = 'blocks';
    case Other = 'other';

    public static function ofJournal(ManualOperationType $type): self
    {
        return match ($type) {
            ManualOperationType::Buy, ManualOperationType::Short, ManualOperationType::Close => self::Trades,
            ManualOperationType::Block, ManualOperationType::Unblock, ManualOperationType::BlockCash => self::Blocks,
            ManualOperationType::CashAdjustment => self::Money,
        };
    }

    public static function ofBroker(BrokerOperationType $type): self
    {
        return match ($type) {
            BrokerOperationType::Buy,
            BrokerOperationType::Sell,
            BrokerOperationType::SecuritiesIn,
            BrokerOperationType::SecuritiesOut,
            BrokerOperationType::VariationMargin => self::Trades,
            BrokerOperationType::Dividend,
            BrokerOperationType::DividendTax,
            BrokerOperationType::Coupon,
            BrokerOperationType::CouponTax,
            BrokerOperationType::BondRepayment,
            BrokerOperationType::BondMaturity => self::Payouts,
            BrokerOperationType::Deposit, BrokerOperationType::Withdrawal => self::Money,
            BrokerOperationType::Other => self::Other,
            default => self::Fees,
        };
    }

    public static function ofRecord(OperationHistorySource $source): ?self
    {
        return match ($source) {
            OperationHistorySource::Deposit => self::Money,
            OperationHistorySource::Dividend, OperationHistorySource::Coupon => self::Payouts,
            default => null,
        };
    }

    /**
     * @return list<string> the journal types of the category
     */
    public function journalTypes(): array
    {
        return array_values(array_map(
            static fn (ManualOperationType $type) => $type->value,
            array_filter(ManualOperationType::cases(), fn (ManualOperationType $type) => self::ofJournal($type) === $this),
        ));
    }

    /**
     * @return list<string> the broker types of the category
     */
    public function brokerTypes(): array
    {
        return array_values(array_map(
            static fn (BrokerOperationType $type) => $type->value,
            array_filter(BrokerOperationType::cases(), fn (BrokerOperationType $type) => self::ofBroker($type) === $this),
        ));
    }
}
