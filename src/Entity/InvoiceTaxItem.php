<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Entity;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTaxItemInterface;

class InvoiceTaxItem implements InvoiceTaxItemInterface
{
    protected ?int $id = null;

    protected ?InvoiceInterface $invoice = null;

    protected string $label;

    protected string $rate;

    protected int $base;

    protected int $amount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoice(): ?InvoiceInterface
    {
        return $this->invoice;
    }

    public function setInvoice(?InvoiceInterface $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function getBase(): int
    {
        return $this->base;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function initialise(string $label, string $rate, int $base, int $amount): void
    {
        $this->label = $label;
        $this->rate = $rate;
        $this->base = $base;
        $this->amount = $amount;
    }
}
