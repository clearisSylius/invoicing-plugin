<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Form\Extension;

use ClearisSylius\InvoicingPlugin\Validator\Constraints\SpanishTaxId;
use Sylius\Bundle\AddressingBundle\Form\Type\AddressType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Add an optional `taxId` field to every form built on top of Sylius'
 * AddressType (admin order address forms, customer addresses, checkout
 * address step, etc.).
 *
 * Validation runs only when the field is non-empty (the SpanishTaxId
 * constraint short-circuits on null/empty), which keeps B2C checkout
 * frictionless while still rejecting bogus values when B2B customers fill
 * the field in.
 */
final class AddressTypeTaxIdExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [AddressType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->supportsTaxId($options['data_class'] ?? null)) {
            return;
        }

        $builder->add('taxId', TextType::class, [
            'label' => 'clearis.form.address.tax_id',
            'required' => false,
            'empty_data' => null,
            'constraints' => [
                new Length(max: 20, maxMessage: 'clearis.tax_id.too_long'),
                new SpanishTaxId(),
            ],
        ]);
    }

    private function supportsTaxId(mixed $dataClass): bool
    {
        return is_string($dataClass) &&
            method_exists($dataClass, 'getTaxId') &&
            method_exists($dataClass, 'setTaxId');
    }
}
