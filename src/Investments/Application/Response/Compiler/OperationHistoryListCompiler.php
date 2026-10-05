<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\DTO\History\OperationHistoryItemDTO;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Investments\Domain\Journal\ManualOperationType;
use App\Investments\Domain\Operations\History\OperationCategory;
use App\Investments\Domain\Operations\History\OperationHistoryEntry;
use App\Investments\Domain\Operations\History\OperationHistorySource;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<list<OperationHistoryEntry>, list<OperationHistoryItemDTO>>
 */
final readonly class OperationHistoryListCompiler implements CompilerInterface
{
    /**
     * @param list<OperationHistoryEntry> $entry
     * @return list<OperationHistoryItemDTO>
     */
    #[\Override]
    public function compile(mixed $entry): array
    {
        return array_map($this->item(...), $entry);
    }

    private function item(OperationHistoryEntry $entry): OperationHistoryItemDTO
    {
        if ($entry->source === OperationHistorySource::Journal) {
            $type = ManualOperationType::from($entry->type);
            $title = self::journalTitle($type, $entry->amount);
            $category = OperationCategory::ofJournal($type);
            // The journal quotes a bond in percent of its nominal and a future in points
            $priceUnit = match ($entry->instrumentKind) {
                'bond' => 'percent',
                'future' => 'points',
                default => 'money',
            };
        } else {
            // The records of deposits and payouts are typed the way the broker types them
            $type = BrokerOperationType::from($entry->type);
            $title = $type->title();
            $category = OperationCategory::ofRecord($entry->source) ?? OperationCategory::ofBroker($type);
            $priceUnit = 'money';
        }

        return new OperationHistoryItemDTO(
            key:         $entry->source->value . ':' . $entry->id,
            source:      $entry->source->value,
            accountId:   $entry->accountId,
            accountName: $entry->accountName,
            executedAt:  $entry->executedAt->format(\DateTimeInterface::ATOM),
            type:        $entry->type,
            title:       $title,
            category:    $category->value,
            ticker:      $entry->ticker,
            name:        $entry->name,
            quantity:    $entry->quantity,
            price:       $entry->price,
            priceUnit:   $priceUnit,
            commission:  $entry->commission,
            amount:      $entry->amount,
            currency:    $entry->currency,
        );
    }

    private static function journalTitle(ManualOperationType $type, ?string $amount): string
    {
        return match ($type) {
            ManualOperationType::Buy => 'Buy',
            ManualOperationType::Short => 'Short sale',
            ManualOperationType::Close => 'Close',
            ManualOperationType::Block => 'Block',
            ManualOperationType::Unblock => 'Unblock',
            ManualOperationType::CashAdjustment => 'Cash adjustment',
            ManualOperationType::BlockCash => $amount !== null && bccomp($amount, '0', 4) < 0 ? 'Unblock cash' : 'Block cash',
        };
    }
}
