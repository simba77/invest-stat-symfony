<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Instruments;

final readonly class ShowBondDetailsDTO implements ShowInstrumentDetailsDTOInterface
{
    public function __construct(
        public ?string $couponAccumulated,
    ) {
    }
}
