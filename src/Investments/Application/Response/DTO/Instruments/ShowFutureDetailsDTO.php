<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Instruments;

final readonly class ShowFutureDetailsDTO implements ShowInstrumentDetailsDTOInterface
{
    public function __construct(
        public ?string $stepPrice,
    ) {
    }
}
