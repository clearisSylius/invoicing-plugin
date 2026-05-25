<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Form\Type;

use ClearisSylius\InvoicingPlugin\Entity\ChannelInvoicingSettings;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceSeries;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceTemplate;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTriggerEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ChannelInvoicingSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('trigger', ChoiceType::class, [
                'label' => 'clearis.form.channel_settings.trigger',
                'choices' => array_combine(
                    array_values(InvoiceTriggerEnum::labels()),
                    array_keys(InvoiceTriggerEnum::labels()),
                ),
            ])

            // ----- Series ---------------------------------------------
            ->add('standardSeries', EntityType::class, [
                'label' => 'clearis.form.channel_settings.standard_series',
                'class' => InvoiceSeries::class,
                'choice_label' => 'code',
                'required' => false,
            ])
            ->add('rectifyingSeries', EntityType::class, [
                'label' => 'clearis.form.channel_settings.rectifying_series',
                'class' => InvoiceSeries::class,
                'choice_label' => 'code',
                'required' => false,
                'help' => 'clearis.form.channel_settings.rectifying_series_help',
            ])

            // ----- Plantillas -----------------------------------------
            ->add('standardTemplate', EntityType::class, [
                'label' => 'clearis.form.channel_settings.standard_template',
                'class' => InvoiceTemplate::class,
                'choice_label' => 'code',
                'required' => false,
            ])
            ->add('rectifyingTemplate', EntityType::class, [
                'label' => 'clearis.form.channel_settings.rectifying_template',
                'class' => InvoiceTemplate::class,
                'choice_label' => 'code',
                'required' => false,
            ])

            // ----- Email ----------------------------------------------
            ->add('sendEmailOnIssue', CheckboxType::class, [
                'label' => 'clearis.form.channel_settings.send_email_on_issue',
                'required' => false,
            ])
            ->add('senderEmail', EmailType::class, [
                'label' => 'clearis.form.channel_settings.sender_email',
                'help' => 'clearis.form.channel_settings.sender_email_help',
                'required' => false,
            ])
            ->add('senderName', TextType::class, [
                'label' => 'clearis.form.channel_settings.sender_name',
                'help' => 'clearis.form.channel_settings.sender_name_help',
                'required' => false,
            ])

            // ----- Datos fiscales del emisor (sub-form embebido) -----
            //
            // Antes esto era un EntityType que mostraba TODAS las filas de
            // shop_billing_data (incluyendo snapshots históricos de cada
            // factura) como opciones de un select. Ahora editamos
            // directamente la entidad asociada al canal — el snapshotter
            // se sigue encargando de clonar al emitir, así que tocar
            // estos campos solo afecta a facturas FUTURAS.
            ->add('shopBillingData', ShopBillingDataType::class, [
                'label' => 'clearis.form.channel_settings.shop_billing_data',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChannelInvoicingSettings::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'clearis_invoicing_channel_settings';
    }
}
