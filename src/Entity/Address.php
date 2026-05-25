<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Entity;

use ClearisSylius\InvoicingPlugin\Model\AddressInterface;
use Sylius\Component\Core\Model\Address as BaseAddress;

/**
 * Resource override for Sylius core's Address entity. Adds a `tax_id` column
 * (NIF/CIF/NIE), optional, which the plugin uses when snapshotting the
 * buyer's billing address into a BillingData on invoice emission.
 *
 * Wired in `ClearisSyliusInvoicingExtension::prependAddressResourceOverride`.
 */
class Address extends BaseAddress implements AddressInterface
{
    protected ?string $taxId = null;

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTaxId(?string $taxId): void
    {
        $this->taxId = $taxId !== null ? trim($taxId) : null;
        if ($this->taxId === '') {
            $this->taxId = null;
        }
    }
}
