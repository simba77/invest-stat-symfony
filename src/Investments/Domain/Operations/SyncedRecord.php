<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Marks a record built from a broker operation, so that the next sync finds and updates it.
 */
trait SyncedRecord
{
    #[ORM\Column(type: Types::SMALLINT, enumType: RecordSource::class, options: ['default' => 1])]
    private RecordSource $source = RecordSource::Manual;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $externalId = null;

    public function getSource(): RecordSource
    {
        return $this->source;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function isSynced(): bool
    {
        return $this->source === RecordSource::Broker;
    }

    public function markSynced(string $externalId): static
    {
        $this->source = RecordSource::Broker;
        $this->externalId = $externalId;

        return $this;
    }
}
