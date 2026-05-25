<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * Customer billing snapshot at the time the invoice was emitted.
 *
 * Unlike Sylius core's Address, this row never changes. Editing the
 * customer's profile address later does NOT mutate previously emitted
 * invoices.
 */
interface BillingDataInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getFirstName(): ?string;

    public function getLastName(): ?string;

    public function getCompany(): ?string;

    /** Spanish NIF/CIF/NIE (or any tax id). Optional — many B2C invoices have none. */
    public function getTaxId(): ?string;

    public function getStreet(): string;

    public function getCity(): string;

    public function getPostcode(): string;

    public function getProvinceCode(): ?string;

    public function getProvinceName(): ?string;

    public function getCountryCode(): string;

    public function getFullName(): string;
}
