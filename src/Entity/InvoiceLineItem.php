<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Entity;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceLineItemInterface;

class InvoiceLineItem implements InvoiceLineItemInterface
{
    protected ?int $id = null;

    protected ?InvoiceInterface $invoice = null;

    protected string $name;

    protected ?string $variantName = null;

    protected ?string $variantCode = null;

    protected int $quantity;

    protected int $unitPrice;

    protected int $discountedUnitNetPrice;

    protected int $subtotal;

    protected ?string $taxRate = null;

    protected int $taxTotal;

    protected int $total;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function getVariantName(): ?string
    {
        return $this->variantName;
    }

    public function getVariantCode(): ?string
    {
        return $this->variantCode;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function getDiscountedUnitNetPrice(): int
    {
        return $this->discountedUnitNetPrice;
    }

    public function getSubtotal(): int
    {
        return $this->subtotal;
    }

    public function getTaxRate(): ?string
    {
        return $this->taxRate;
    }

    public function getTaxTotal(): int
    {
        return $this->taxTotal;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function initialise(
        string $name,
        ?string $variantName,
        ?string $variantCode,
        int $quantity,
        int $unitPrice,
        int $discountedUnitNetPrice,
        int $subtotal,
        ?string $taxRate,
        int $taxTotal,
        int $total,
    ): void {
        $this->name = $name;
        $this->variantName = $variantName;
        $this->variantCode = $variantCode;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->discountedUnitNetPrice = $discountedUnitNetPrice;
        $this->subtotal = $subtotal;
        $this->taxRate = $taxRate;
        $this->taxTotal = $taxTotal;
        $this->total = $total;
    }
}
