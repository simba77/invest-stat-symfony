<?php

declare(strict_types=1);

namespace App\Tests\Investments\BrokerSync;

use App\Investments\Domain\BrokerSync\BrokerOperationState;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\BrokerSync\Client\ExternalOperation;
use App\Investments\Domain\BrokerSync\InstrumentKind;

/**
 * Broker operations for tests, with the details a scenario does not care about filled in.
 */
final class Operations
{
    /**
     * @param numeric-string $amount
     */
    public static function deposit(string $id, string $date, string $amount): ExternalOperation
    {
        return self::money($id, BrokerOperationType::Deposit, $date, $amount);
    }

    /**
     * @param numeric-string $amount negative
     */
    public static function withdrawal(string $id, string $date, string $amount): ExternalOperation
    {
        return self::money($id, BrokerOperationType::Withdrawal, $date, $amount);
    }

    /**
     * @param numeric-string $price
     * @param numeric-string|null $commission
     * @param numeric-string|null $accruedInterest
     */
    public static function buy(
        string $id,
        string $date,
        string $ticker,
        int $quantity,
        string $price,
        ?string $commission = null,
        InstrumentKind $kind = InstrumentKind::Share,
        string $classCode = 'TQBR',
        ?string $accruedInterest = null,
        BrokerOperationState $state = BrokerOperationState::Executed,
    ): ExternalOperation {
        $amount = bcadd(bcmul($price, (string) $quantity, 9), $accruedInterest ?? '0', 9);

        return self::trade($id, BrokerOperationType::Buy, $date, $ticker, $quantity, $price, bcmul($amount, '-1', 9), $commission, $kind, $classCode, $accruedInterest, $state);
    }

    /**
     * @param numeric-string $price
     * @param numeric-string|null $commission
     * @param numeric-string|null $accruedInterest
     */
    public static function sell(
        string $id,
        string $date,
        string $ticker,
        int $quantity,
        string $price,
        ?string $commission = null,
        InstrumentKind $kind = InstrumentKind::Share,
        string $classCode = 'TQBR',
        ?string $accruedInterest = null,
    ): ExternalOperation {
        $amount = bcadd(bcmul($price, (string) $quantity, 9), $accruedInterest ?? '0', 9);

        return self::trade($id, BrokerOperationType::Sell, $date, $ticker, $quantity, $price, $amount, $commission, $kind, $classCode, $accruedInterest);
    }

    /**
     * @param numeric-string $amount negative
     */
    public static function fee(
        string $id,
        string $date,
        string $amount,
        BrokerOperationType $type = BrokerOperationType::TradeFee,
        ?string $parentId = null,
        ?string $ticker = null,
    ): ExternalOperation {
        return new ExternalOperation(
            id:              $id,
            parentId:        $parentId,
            type:            $type,
            rawType:         'OPERATION_TYPE_' . strtoupper($type->value),
            state:           BrokerOperationState::Executed,
            executedAt:      new \DateTimeImmutable($date),
            instrumentUid:   $ticker !== null ? self::uid($ticker) : null,
            instrumentKind:  $ticker !== null ? InstrumentKind::Share : null,
            ticker:          $ticker,
            classCode:       $ticker !== null ? 'TQBR' : null,
            name:            null,
            quantity:        0,
            price:           null,
            payment:         $amount,
            currency:        'RUB',
            commission:      null,
            accruedInterest: null,
            description:     null,
            payload:         [],
        );
    }

    /**
     * @param numeric-string $amount
     */
    public static function payout(
        string $id,
        BrokerOperationType $type,
        string $date,
        string $ticker,
        string $amount,
        InstrumentKind $kind = InstrumentKind::Share,
        string $classCode = 'TQBR',
    ): ExternalOperation {
        return new ExternalOperation(
            id:              $id,
            parentId:        null,
            type:            $type,
            rawType:         'OPERATION_TYPE_' . strtoupper($type->value),
            state:           BrokerOperationState::Executed,
            executedAt:      new \DateTimeImmutable($date),
            instrumentUid:   self::uid($ticker),
            instrumentKind:  $kind,
            ticker:          $ticker,
            classCode:       $classCode,
            name:            null,
            quantity:        0,
            price:           null,
            payment:         $amount,
            currency:        'RUB',
            commission:      null,
            accruedInterest: null,
            description:     null,
            payload:         [],
        );
    }

    /**
     * Instruments in tests are identified by a uid derived from the ticker.
     */
    public static function uid(string $ticker): string
    {
        return 'uid-' . $ticker;
    }

    /**
     * @param numeric-string $amount
     */
    private static function money(string $id, BrokerOperationType $type, string $date, string $amount): ExternalOperation
    {
        return new ExternalOperation(
            id:              $id,
            parentId:        null,
            type:            $type,
            rawType:         'OPERATION_TYPE_' . strtoupper($type->value),
            state:           BrokerOperationState::Executed,
            executedAt:      new \DateTimeImmutable($date),
            instrumentUid:   null,
            instrumentKind:  null,
            ticker:          null,
            classCode:       null,
            name:            null,
            quantity:        0,
            price:           null,
            payment:         $amount,
            currency:        'RUB',
            commission:      null,
            accruedInterest: null,
            description:     null,
            payload:         [],
        );
    }

    /**
     * @param numeric-string $price
     * @param numeric-string $payment
     * @param numeric-string|null $commission
     * @param numeric-string|null $accruedInterest
     */
    private static function trade(
        string $id,
        BrokerOperationType $type,
        string $date,
        string $ticker,
        int $quantity,
        string $price,
        string $payment,
        ?string $commission,
        InstrumentKind $kind,
        string $classCode,
        ?string $accruedInterest,
        BrokerOperationState $state = BrokerOperationState::Executed,
    ): ExternalOperation {
        return new ExternalOperation(
            id:              $id,
            parentId:        null,
            type:            $type,
            rawType:         'OPERATION_TYPE_' . strtoupper($type->value),
            state:           $state,
            executedAt:      new \DateTimeImmutable($date),
            instrumentUid:   self::uid($ticker),
            instrumentKind:  $kind,
            ticker:          $ticker,
            classCode:       $classCode,
            name:            $ticker . ' name',
            quantity:        $quantity,
            price:           $price,
            payment:         $payment,
            currency:        'RUB',
            commission:      $commission,
            accruedInterest: $accruedInterest,
            description:     null,
            payload:         [],
        );
    }
}
