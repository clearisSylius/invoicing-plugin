<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Entity;

use ClearisSylius\InvoicingPlugin\Model\BillingDataInterface;

class BillingData implements BillingDataInterface
{
    protected ?int $id = null;

    protected ?string $firstName = null;

    protected ?string $lastName = null;

    protected ?string $company = null;

    protected ?string $taxId = null;

    protected string $street;

    protected string $city;

    protected string $postcode;

    protected ?string $provinceCode = null;

    protected ?string $provinceName = null;

    protected string $countryCode;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getTaxId(): ?string
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

    public function getProvinceCode(): ?string
    {
        return $this->provinceCode;
    }

    public function getProvinceName(): ?string
    {
        return $this->provinceName;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Renders the snapshot's human name. The company takes precedence — when
     * present, it is the legal counterparty on the invoice; the person name
     * is shown in parentheses if both are set, useful for B2B invoices to a
     * specific contact.
     */
    public function getFullName(): string
    {
        $first = trim((string) $this->firstName);
        $last = trim((string) $this->lastName);
        $company = trim((string) $this->company);

        $person = trim($first . ' ' . $last);
        if ($company !== '' && $person !== '') {
            return sprintf('%s (%s)', $company, $person);
        }
        if ($company !== '') {
            return $company;
        }

        return $person;
    }

    public function initialise(
        ?string $firstName,
        ?string $lastName,
        ?string $company,
        ?string $taxId,
        string $street,
        string $city,
        string $postcode,
        ?string $provinceCode,
        ?string $provinceName,
        string $countryCode,
    ): void {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->company = $company;
        $this->taxId = $taxId;
        $this->street = $street;
        $this->city = $city;
        $this->postcode = $postcode;
        $this->provinceCode = $provinceCode;
        $this->provinceName = $provinceName;
        $this->countryCode = $countryCode;
    }
}
