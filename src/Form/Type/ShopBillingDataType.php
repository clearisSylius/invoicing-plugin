<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Form\Type;

use ClearisSylius\InvoicingPlugin\Entity\ShopBillingData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Datos fiscales del emisor — formulario embedido dentro del form de
 * `ChannelInvoicingSettings`.
 *
 * Antes este campo era un `EntityType` que mostraba TODAS las filas de
 * `clearis_invoicing_shop_billing_data` como opciones de un select; eso
 * incluía las snapshots históricas que el `ShopBillingDataSnapshotter`
 * clona en CADA factura emitida — el admin veía 30+ entradas idénticas
 * "Sylius" sin forma de distinguirlas. Ahora editamos directamente los
 * campos de la entidad asociada al canal y no nos contamina con las
 * snapshots inmutables.
 *
 * Sobre las snapshots: el `Invoice.shopBillingData` es una COPIA (clon)
 * hecha en el momento de la emisión, así que editar este formulario solo
 * afecta a facturas FUTURAS — las ya emitidas conservan los datos
 * fiscales que tenía la empresa en su momento (lo correcto fiscalmente).
 */
final class ShopBillingDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'clearis.form.shop_billing.company_name',
                'required' => false,
            ])
            ->add('taxId', TextType::class, [
                'label' => 'clearis.form.shop_billing.tax_id',
                'help' => 'clearis.form.shop_billing.tax_id_help',
                'required' => false,
            ])
            ->add('street', TextType::class, [
                'label' => 'clearis.form.shop_billing.street',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'clearis.form.shop_billing.city',
                'required' => false,
            ])
            ->add('postcode', TextType::class, [
                'label' => 'clearis.form.shop_billing.postcode',
                'required' => false,
            ])
            ->add('countryCode', CountryType::class, [
                'label' => 'clearis.form.shop_billing.country_code',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShopBillingData::class,
            // Si el ChannelInvoicingSettings aún no tiene `shopBillingData`
            // (recién creado), Symfony instancia uno automáticamente al
            // popular el sub-form gracias a este `empty_data`.
            'empty_data' => static fn () => new ShopBillingData(),
            'required' => false,
            // El bloque de campos es opcional como tal: si el admin no
            // rellena ningún campo el SBD queda con todos los strings
            // requeridos vacíos. La validación de "todo o nada" la
            // gestionamos en el data event listener de abajo.
            'inherit_data' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'clearis_invoicing_shop_billing_data';
    }
}
