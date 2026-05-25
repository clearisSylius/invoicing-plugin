<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Entity;

use ClearisSylius\InvoicingPlugin\Model\ShopBillingDataInterface;

class ShopBillingData implements ShopBillingDataInterface
{
    protected ?int $id = null;

    protected string $companyName;

    protected string $taxId;

    protected string $street;

    protected string $city;

    protected string $postcode;

    protected string $countryCode;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getTaxId(): string
    {
        return $this->taxId;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function initialise(
        string $companyName,
        string $taxId,
        string $street,
        string $city,
        string $postcode,
        string $countryCode,
    ): void {
        $this->companyName = $companyName;
        $this->taxId = $taxId;
        $this->street = $street;
        $this->city = $city;
        $this->postcode = $postcode;
        $this->countryCode = $countryCode;
    }

    /**
     * Used by the form when admins edit the shop billing data per channel.
     * These setters are intentionally NOT exposed for invoice snapshots
     * because once a ShopBillingData has been attached to an Invoice, the
     * factory clones it (see ShopBillingDataSnapshotter).
     */
    public function setCompanyName(string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function setTaxId(string $taxId): void
    {
        $this->taxId = $taxId;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function setPostcode(string $postcode): void
    {
        $this->postcode = $postcode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }
}
