<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * Issuer (shop) billing snapshot. Frozen on the invoice so that if the shop
 * changes its registered address or fiscal name later, prior invoices keep
 * the original details.
 */
interface ShopBillingDataInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getCompanyName(): string;

    public function getTaxId(): string;

    public function getStreet(): string;

    public function getCity(): string;

    public function getPostcode(): string;

    public function getCountryCode(): string;
}
