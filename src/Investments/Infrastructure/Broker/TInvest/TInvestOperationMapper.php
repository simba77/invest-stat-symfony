<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Broker\TInvest;

use App\Investments\Domain\BrokerSync\BrokerOperationState;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\BrokerSync\Client\ExternalOperation;
use App\Investments\Domain\BrokerSync\InstrumentKind;
use Tinkoff\Invest\V1\InstrumentType;
use Tinkoff\Invest\V1\MoneyValue;
use Tinkoff\Invest\V1\OperationItem;
use Tinkoff\Invest\V1\OperationState;
use Tinkoff\Invest\V1\OperationType;

final class TInvestOperationMapper
{
    public function map(OperationItem $item): ExternalOperation
    {
        $payment = $this->money($item->getPayment()) ?? '0';
        $currency = $item->getPayment()?->getCurrency() ?: $item->getPrice()?->getCurrency() ?: 'rub';

        /** @var array<string, mixed> $payload */
        $payload = json_decode($item->serializeToJsonString(), true, flags: JSON_THROW_ON_ERROR);

        return new ExternalOperation(
            id:              $item->getId(),
            parentId:        $item->getParentOperationId() !== '' ? $item->getParentOperationId() : null,
            type:            $this->type($item->getType(), $payment),
            rawType:         OperationType::name($item->getType()),
            state:           $this->state($item->getState()),
            executedAt:      TInvestValues::dateTime($item->getDate() ?? throw new \RuntimeException('Operation without date: ' . $item->getId())),
            instrumentUid:   $item->getInstrumentUid() !== '' ? $item->getInstrumentUid() : null,
            instrumentKind:  $this->instrumentKind($item->getInstrumentKind()),
            ticker:          $item->getTicker() !== '' ? $item->getTicker() : null,
            classCode:       $item->getClassCode() !== '' ? $item->getClassCode() : null,
            name:            $item->getName() !== '' ? $item->getName() : null,
            quantity:        $this->quantity($item),
            price:           $this->price($item),
            payment:         $payment,
            currency:        strtoupper($currency),
            commission:      $this->absolute($this->money($item->getCommission())),
            accruedInterest: $this->absolute($this->money($item->getAccruedInt())),
            description:     $item->getDescription() !== '' ? $item->getDescription() : null,
            payload:         $payload,
        );
    }

    /**
     * @param numeric-string $payment
     */
    private function type(int $type, string $payment): BrokerOperationType
    {
        return match ($type) {
            OperationType::OPERATION_TYPE_INPUT,
            OperationType::OPERATION_TYPE_INPUT_SWIFT,
            OperationType::OPERATION_TYPE_INPUT_ACQUIRING => BrokerOperationType::Deposit,
            OperationType::OPERATION_TYPE_OUTPUT,
            OperationType::OPERATION_TYPE_OUTPUT_SWIFT,
            OperationType::OPERATION_TYPE_OUTPUT_ACQUIRING => BrokerOperationType::Withdrawal,
            // Money moved between accounts of the same client
            OperationType::OPERATION_TYPE_TRANS_IIS_BS,
            OperationType::OPERATION_TYPE_TRANS_BS_BS => bccomp($payment, '0', 9) >= 0
                ? BrokerOperationType::Deposit
                : BrokerOperationType::Withdrawal,
            OperationType::OPERATION_TYPE_BUY,
            OperationType::OPERATION_TYPE_BUY_CARD,
            OperationType::OPERATION_TYPE_BUY_MARGIN,
            OperationType::OPERATION_TYPE_DELIVERY_BUY,
            OperationType::OPERATION_TYPE_PRIMARY_ORDER => BrokerOperationType::Buy,
            OperationType::OPERATION_TYPE_SELL,
            OperationType::OPERATION_TYPE_SELL_CARD,
            OperationType::OPERATION_TYPE_SELL_MARGIN,
            OperationType::OPERATION_TYPE_DELIVERY_SELL => BrokerOperationType::Sell,
            OperationType::OPERATION_TYPE_BROKER_FEE => BrokerOperationType::TradeFee,
            OperationType::OPERATION_TYPE_TRACK_MFEE => BrokerOperationType::ManagementFee,
            OperationType::OPERATION_TYPE_TRACK_PFEE,
            OperationType::OPERATION_TYPE_SUCCESS_FEE => BrokerOperationType::PerformanceFee,
            OperationType::OPERATION_TYPE_SERVICE_FEE,
            OperationType::OPERATION_TYPE_ADVICE_FEE => BrokerOperationType::ServiceFee,
            OperationType::OPERATION_TYPE_MARGIN_FEE => BrokerOperationType::MarginFee,
            OperationType::OPERATION_TYPE_CASH_FEE,
            OperationType::OPERATION_TYPE_OUT_FEE,
            OperationType::OPERATION_TYPE_OUT_STAMP_DUTY,
            OperationType::OPERATION_TYPE_OUTPUT_PENALTY,
            OperationType::OPERATION_TYPE_OVER_COM,
            OperationType::OPERATION_TYPE_OTHER_FEE => BrokerOperationType::OtherFee,
            OperationType::OPERATION_TYPE_DIVIDEND,
            OperationType::OPERATION_TYPE_DIV_EXT,
            OperationType::OPERATION_TYPE_DIVIDEND_TRANSFER => BrokerOperationType::Dividend,
            OperationType::OPERATION_TYPE_DIVIDEND_TAX,
            OperationType::OPERATION_TYPE_DIVIDEND_TAX_PROGRESSIVE => BrokerOperationType::DividendTax,
            OperationType::OPERATION_TYPE_COUPON => BrokerOperationType::Coupon,
            OperationType::OPERATION_TYPE_BOND_TAX,
            OperationType::OPERATION_TYPE_BOND_TAX_PROGRESSIVE => BrokerOperationType::CouponTax,
            OperationType::OPERATION_TYPE_BOND_REPAYMENT => BrokerOperationType::BondRepayment,
            OperationType::OPERATION_TYPE_BOND_REPAYMENT_FULL,
            OperationType::OPERATION_TYPE_DFA_REDEMPTION => BrokerOperationType::BondMaturity,
            OperationType::OPERATION_TYPE_TAX,
            OperationType::OPERATION_TYPE_TAX_PROGRESSIVE,
            OperationType::OPERATION_TYPE_BENEFIT_TAX,
            OperationType::OPERATION_TYPE_BENEFIT_TAX_PROGRESSIVE,
            OperationType::OPERATION_TYPE_TAX_REPO,
            OperationType::OPERATION_TYPE_TAX_REPO_HOLD,
            OperationType::OPERATION_TYPE_TAX_REPO_PROGRESSIVE,
            OperationType::OPERATION_TYPE_TAX_REPO_HOLD_PROGRESSIVE => BrokerOperationType::Tax,
            OperationType::OPERATION_TYPE_TAX_CORRECTION,
            OperationType::OPERATION_TYPE_TAX_CORRECTION_PROGRESSIVE,
            OperationType::OPERATION_TYPE_TAX_CORRECTION_COUPON,
            OperationType::OPERATION_TYPE_TAX_REPO_REFUND,
            OperationType::OPERATION_TYPE_TAX_REPO_REFUND_PROGRESSIVE => BrokerOperationType::TaxCorrection,
            OperationType::OPERATION_TYPE_ACCRUING_VARMARGIN,
            OperationType::OPERATION_TYPE_WRITING_OFF_VARMARGIN => BrokerOperationType::VariationMargin,
            OperationType::OPERATION_TYPE_INPUT_SECURITIES,
            OperationType::OPERATION_TYPE_INP_MULTI => BrokerOperationType::SecuritiesIn,
            OperationType::OPERATION_TYPE_OUTPUT_SECURITIES,
            OperationType::OPERATION_TYPE_OUT_MULTI => BrokerOperationType::SecuritiesOut,
            default => BrokerOperationType::Other,
        };
    }

    private function state(int $state): BrokerOperationState
    {
        return match ($state) {
            OperationState::OPERATION_STATE_EXECUTED => BrokerOperationState::Executed,
            OperationState::OPERATION_STATE_CANCELED => BrokerOperationState::Canceled,
            default => BrokerOperationState::InProgress,
        };
    }

    private function instrumentKind(int $kind): ?InstrumentKind
    {
        return match ($kind) {
            InstrumentType::INSTRUMENT_TYPE_UNSPECIFIED => null,
            InstrumentType::INSTRUMENT_TYPE_SHARE => InstrumentKind::Share,
            InstrumentType::INSTRUMENT_TYPE_ETF => InstrumentKind::Etf,
            InstrumentType::INSTRUMENT_TYPE_BOND => InstrumentKind::Bond,
            InstrumentType::INSTRUMENT_TYPE_FUTURES => InstrumentKind::Future,
            InstrumentType::INSTRUMENT_TYPE_CURRENCY => InstrumentKind::Currency,
            default => InstrumentKind::Other,
        };
    }

    /**
     * The executed part of an order: a partially filled order reports the requested quantity too.
     */
    private function quantity(OperationItem $item): int
    {
        $done = (int) $item->getQuantityDone();

        return $done > 0 ? $done : (int) $item->getQuantity();
    }

    /**
     * An order filled in several trades reports a rounded average; the trades give the exact one.
     *
     * @return numeric-string|null
     */
    private function price(OperationItem $item): ?string
    {
        $quantity = '0';
        $amount = '0';
        foreach ($item->getTradesInfo()?->getTrades() ?? [] as $trade) {
            $price = $this->money($trade->getPrice());
            if ($price === null) {
                continue;
            }
            $quantity = bcadd($quantity, (string) $trade->getQuantity());
            $amount = bcadd($amount, bcmul($price, (string) $trade->getQuantity(), 9), 9);
        }

        if (bccomp($quantity, '0') > 0) {
            return bcdiv($amount, $quantity, 9);
        }

        $price = $this->money($item->getPrice());

        return $price !== null && bccomp($price, '0', 9) !== 0 ? $price : null;
    }

    /**
     * @return numeric-string|null
     */
    private function money(?MoneyValue $value): ?string
    {
        return $value !== null ? TInvestValues::decimal($value->getUnits(), $value->getNano()) : null;
    }

    /**
     * @param numeric-string|null $value
     * @return numeric-string|null
     */
    private function absolute(?string $value): ?string
    {
        if ($value === null || bccomp($value, '0', 9) === 0) {
            return null;
        }

        return bccomp($value, '0', 9) < 0 ? bcmul($value, '-1', 9) : $value;
    }
}
