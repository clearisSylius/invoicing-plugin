<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Factory;

use ClearisSylius\InvoicingPlugin\Entity\ShopBillingData;
use ClearisSylius\InvoicingPlugin\Model\ShopBillingDataInterface;

/**
 * Builds a fresh ShopBillingData row each time an invoice is emitted, copying
 * the live channel-level data. Cloning the existing record (rather than
 * referencing it) is essential — if the admin later edits the shop billing
 * info, prior invoices must still reflect the old details.
 */
final class ShopBillingDataSnapshotter
{
    public function snapshot(ShopBillingDataInterface $source): ShopBillingDataInterface
    {
        $copy = new ShopBillingData();
        $copy->initialise(
            companyName: $source->getCompanyName(),
            taxId: $source->getTaxId(),
            street: $source->getStreet(),
            city: $source->getCity(),
            postcode: $source->getPostcode(),
            countryCode: $source->getCountryCode(),
        );

        return $copy;
    }
}
