<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Entity;

use ClearisSylius\InvoicingPlugin\Model\InvoiceSeriesInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTypeEnum;
use Sylius\Component\Channel\Model\ChannelInterface;

class InvoiceSeries implements InvoiceSeriesInterface
{
    protected ?int $id = null;

    protected string $code;

    protected ?string $name = null;

    protected ?ChannelInterface $channel = null;

    /** @phpstan-var InvoiceTypeEnum::* */
    protected string $type = InvoiceTypeEnum::STANDARD;

    /**
     * Format string. Supported placeholders:
     *  - {year}    : 4-digit emission year (issuedAt)
     *  - {number}  : zero-padded counter, width = `padding`
     *  - {prefix}  : the `code` of the series (handy for letter prefixes)
     *
     * Examples:
     *  - "{year}/{number}"   ->  "2026/0001"
     *  - "F-{year}-{number}" ->  "F-2026-0001"
     *  - "R{year}{number}"   ->  "R20260001"
     */
    protected string $format = '{year}/{number}';

    protected int $padding = 4;

    protected int $currentNumber = 0;

    protected ?int $lastYearReset = null;

    protected bool $yearlyReset = true;

    protected bool $active = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @phpstan-param InvoiceTypeEnum::* $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    public function getPadding(): int
    {
        return $this->padding;
    }

    public function setPadding(int $padding): void
    {
        $this->padding = max(1, $padding);
    }

    public function getCurrentNumber(): int
    {
        return $this->currentNumber;
    }

    public function setCurrentNumber(int $currentNumber): void
    {
        $this->currentNumber = $currentNumber;
    }

    public function getLastYearReset(): ?int
    {
        return $this->lastYearReset;
    }

    public function setLastYearReset(?int $year): void
    {
        $this->lastYearReset = $year;
    }

    public function isYearlyReset(): bool
    {
        return $this->yearlyReset;
    }

    public function setYearlyReset(bool $yearlyReset): void
    {
        $this->yearlyReset = $yearlyReset;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * Renders a candidate number string using the current counter + supplied year.
     * The generator calls this AFTER incrementing the counter atomically.
     */
    public function renderNumber(int $year): string
    {
        return strtr($this->format, [
            '{year}' => (string) $year,
            '{prefix}' => $this->code,
            '{number}' => str_pad((string) $this->currentNumber, $this->padding, '0', \STR_PAD_LEFT),
        ]);
    }
}
