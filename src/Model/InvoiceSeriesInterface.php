<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * Numbering series tied to (channel, type). At most one series may be active
 * per (channel, type) at any given time; this is enforced at DB level by a
 * partial unique index.
 */
interface InvoiceSeriesInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getCode(): string;

    public function setCode(string $code): void;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    /** @return InvoiceTypeEnum::* */
    public function getType(): string;

    public function setType(string $type): void;

    /** Format string used to render the next number, e.g. "{year}/{number}". */
    public function getFormat(): string;

    public function setFormat(string $format): void;

    public function getPadding(): int;

    public function setPadding(int $padding): void;

    public function getCurrentNumber(): int;

    public function setCurrentNumber(int $currentNumber): void;

    public function getLastYearReset(): ?int;

    public function setLastYearReset(?int $year): void;

    public function isYearlyReset(): bool;

    public function setYearlyReset(bool $yearlyReset): void;

    public function isActive(): bool;

    public function setActive(bool $active): void;
}
