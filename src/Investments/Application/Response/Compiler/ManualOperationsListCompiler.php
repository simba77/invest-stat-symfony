<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\DTO\Journal\ManualOperationItemDTO;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Journal\ManualOperationType;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<array{operations: list<ManualOperation>, openings: array<string, ManualOperation>}, list<ManualOperationItemDTO>>
 */
final readonly class ManualOperationsListCompiler implements CompilerInterface
{
    /**
     * @param array{operations: list<ManualOperation>, openings: array<string, ManualOperation>} $entry the operations
     *        and the openings of the lots they are about
     * @return list<ManualOperationItemDTO>
     */
    #[\Override]
    public function compile(mixed $entry): array
    {
        $result = [];
        foreach ($entry['operations'] as $operation) {
            $lot = $operation->getLot();
            $opening = $lot !== null ? ($entry['openings'][$lot] ?? null) : null;
            // A block of a lot keeps no security: it is the one the lot was opened with
            $security = $operation->getTicker() !== null ? $operation : $opening;
            $instrument = $security?->getInstrument();
            $instrumentType = match (true) {
                $instrument instanceof Bond => SecurityTypeEnum::Bond->getCode(),
                $instrument instanceof Future => SecurityTypeEnum::Future->getCode(),
                $instrument !== null => SecurityTypeEnum::Share->getCode(),
                default => null,
            };
            $isTrade = in_array($operation->getType(), [ManualOperationType::Buy, ManualOperationType::Short, ManualOperationType::Close], true);
            $isCash = in_array($operation->getType(), [ManualOperationType::CashAdjustment, ManualOperationType::BlockCash], true);

            $result[] = new ManualOperationItemDTO(
                id:             (int) $operation->getId(),
                type:           $operation->getType()->value,
                executedAt:     $operation->getExecutedAt()->format(\DateTimeInterface::ATOM),
                ticker:         $security?->getTicker(),
                name:           $instrument !== null ? ($instrument->getShortName() ?? $instrument->getName()) : null,
                instrumentType: $instrumentType,
                quantity:       $isTrade ? $operation->getQuantity() : null,
                price:          $isTrade ? $operation->getPrice() : null,
                commission:     $operation->getCommission(),
                amount:         $isCash ? $operation->getAmount() : null,
                currency:       $isCash ? $operation->getCurrency() : $instrument?->getCurrency(),
                lotOpenedAt:    $opening?->getExecutedAt()->format(\DateTimeInterface::ATOM),
                lotPrice:       $opening?->getPrice(),
            );
        }

        return $result;
    }
}
