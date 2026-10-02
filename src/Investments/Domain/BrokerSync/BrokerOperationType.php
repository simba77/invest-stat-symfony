<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

/**
 * What a broker operation means for the account, independent of the broker's own type codes.
 */
enum BrokerOperationType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Buy = 'buy';
    case Sell = 'sell';
    case TradeFee = 'trade_fee';
    case ManagementFee = 'management_fee';
    case PerformanceFee = 'performance_fee';
    case ServiceFee = 'service_fee';
    case MarginFee = 'margin_fee';
    case OtherFee = 'other_fee';
    case Dividend = 'dividend';
    case DividendTax = 'dividend_tax';
    case Coupon = 'coupon';
    case CouponTax = 'coupon_tax';
    /** Partial redemption of the bond nominal (amortization). */
    case BondRepayment = 'bond_repayment';
    /** Redemption of the whole nominal: the bond leaves the account. */
    case BondMaturity = 'bond_maturity';
    case Tax = 'tax';
    case TaxCorrection = 'tax_correction';
    case VariationMargin = 'variation_margin';
    case SecuritiesIn = 'securities_in';
    case SecuritiesOut = 'securities_out';
    case Other = 'other';

    /**
     * Fees and taxes of the account that do not belong to a trade or a payout.
     */
    public function isAccountExpense(): bool
    {
        return match ($this) {
            self::ManagementFee,
            self::PerformanceFee,
            self::ServiceFee,
            self::MarginFee,
            self::OtherFee,
            self::Tax,
            self::TaxCorrection => true,
            default => false,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit',
            self::Withdrawal => 'Withdrawal',
            self::Buy => 'Buy',
            self::Sell => 'Sell',
            self::TradeFee => 'Trade fee',
            self::ManagementFee => 'Management fee',
            self::PerformanceFee => 'Performance fee',
            self::ServiceFee => 'Service fee',
            self::MarginFee => 'Margin fee',
            self::OtherFee => 'Other fee',
            self::Dividend => 'Dividend',
            self::DividendTax => 'Dividend tax',
            self::Coupon => 'Coupon',
            self::CouponTax => 'Coupon tax',
            self::BondRepayment => 'Bond repayment',
            self::BondMaturity => 'Bond maturity',
            self::Tax => 'Tax',
            self::TaxCorrection => 'Tax correction',
            self::VariationMargin => 'Variation margin',
            self::SecuritiesIn => 'Securities in',
            self::SecuritiesOut => 'Securities out',
            self::Other => 'Other',
        };
    }
}
