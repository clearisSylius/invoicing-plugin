<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Factory;

use ClearisSylius\InvoicingPlugin\Entity\BillingData;
use ClearisSylius\InvoicingPlugin\Model\AddressInterface;
use ClearisSylius\InvoicingPlugin\Model\BillingDataInterface;
use Sylius\Component\Core\Model\AddressInterface as SyliusAddressInterface;

/**
 * Builds a frozen BillingData snapshot from a Sylius Address. The snapshot
 * is a brand-new entity, never a reference to the live address — that's
 * what gives the invoice its immutability against later edits.
 */
final class BillingDataSnapshotter
{
    public function snapshot(SyliusAddressInterface $address): BillingDataInterface
    {
        $billingData = new BillingData();

        $taxId = $address instanceof AddressInterface ? $address->getTaxId() : null;

        $billingData->initialise(
            firstName: $address->getFirstName(),
            lastName: $address->getLastName(),
            company: $address->getCompany(),
            taxId: $taxId,
            street: (string) $address->getStreet(),
            city: (string) $address->getCity(),
            postcode: (string) $address->getPostcode(),
            provinceCode: $address->getProvinceCode(),
            provinceName: $address->getProvinceName(),
            countryCode: (string) $address->getCountryCode(),
        );

        return $billingData;
    }
}
