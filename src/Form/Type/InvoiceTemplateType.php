<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Form\Type;

use ClearisSylius\InvoicingPlugin\Entity\InvoiceTemplate;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTypeEnum;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form completo para personalizar una plantilla PDF.
 *
 * Los campos están agrupados en bloques visuales con `label_attr.class` que
 * añade una etiqueta tipo "h5" arriba de cada sección. El form theme
 * Bootstrap 5 que aplica Sylius en el CRUD admin los renderiza bien.
 *
 * Decisión de diseño: NO usamos sub-form types ni Constraints aquí — un
 * único formulario plano es lo suficientemente legible para 23 campos y
 * evita la indirección de tener N FormType de wrapping.
 */
final class InvoiceTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ----- Identidad -------------------------------------------
            ->add('code', TextType::class, [
                'label' => 'clearis.form.template.code',
            ])
            ->add('name', TextType::class, [
                'label' => 'clearis.form.template.name',
                'required' => false,
            ])
            ->add('channel', ChannelChoiceType::class, [
                'label' => 'clearis.form.template.channel',
                'required' => false,
                'help' => 'clearis.form.template.channel_help',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'clearis.form.template.type',
                'choices' => [
                    'clearis.form.template.type_standard' => InvoiceTypeEnum::STANDARD,
                    'clearis.form.template.type_rectifying' => InvoiceTypeEnum::RECTIFYING,
                ],
            ])

            // ----- Branding --------------------------------------------
            ->add('showLogo', CheckboxType::class, [
                'label' => 'clearis.form.template.show_logo',
                'required' => false,
            ])
            ->add('logoPath', TextType::class, [
                'label' => 'clearis.form.template.logo_path',
                'help' => 'clearis.form.template.logo_path_help',
                'required' => false,
            ])
            ->add('accentColor', ColorType::class, [
                'label' => 'clearis.form.template.accent_color',
                'help' => 'clearis.form.template.accent_color_help',
                'required' => true,
            ])
            ->add('layoutDensity', ChoiceType::class, [
                'label' => 'clearis.form.template.layout_density',
                'choices' => [
                    'clearis.form.template.density_compact' => 'compact',
                    'clearis.form.template.density_regular' => 'regular',
                    'clearis.form.template.density_spacious' => 'spacious',
                ],
            ])

            // ----- Cabecera --------------------------------------------
            ->add('headerText', TextareaType::class, [
                'label' => 'clearis.form.template.header_text',
                'help' => 'clearis.form.template.header_text_help',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('headerContactInfo', TextareaType::class, [
                'label' => 'clearis.form.template.header_contact_info',
                'help' => 'clearis.form.template.header_contact_info_help',
                'required' => false,
                'attr' => ['rows' => 3],
            ])

            // ----- Cliente ---------------------------------------------
            ->add('showCustomerEmail', CheckboxType::class, [
                'label' => 'clearis.form.template.show_customer_email',
                'required' => false,
            ])
            ->add('showCustomerShippingAddress', CheckboxType::class, [
                'label' => 'clearis.form.template.show_customer_shipping_address',
                'required' => false,
            ])

            // ----- Líneas ----------------------------------------------
            ->add('showLineSku', CheckboxType::class, [
                'label' => 'clearis.form.template.show_line_sku',
                'required' => false,
            ])
            ->add('showLineExtendedDescription', CheckboxType::class, [
                'label' => 'clearis.form.template.show_line_extended_description',
                'required' => false,
            ])
            ->add('showLineDiscounts', CheckboxType::class, [
                'label' => 'clearis.form.template.show_line_discounts',
                'required' => false,
            ])

            // ----- Totales / IVA ---------------------------------------
            ->add('showTaxBreakdown', CheckboxType::class, [
                'label' => 'clearis.form.template.show_tax_breakdown',
                'required' => false,
            ])

            // ----- Referencias del pedido ------------------------------
            ->add('showOrderNumber', CheckboxType::class, [
                'label' => 'clearis.form.template.show_order_number',
                'required' => false,
            ])
            ->add('showPaymentMethod', CheckboxType::class, [
                'label' => 'clearis.form.template.show_payment_method',
                'required' => false,
            ])
            ->add('showShippingMethod', CheckboxType::class, [
                'label' => 'clearis.form.template.show_shipping_method',
                'required' => false,
            ])

            // ----- Bloques de texto libres -----------------------------
            ->add('paymentTermsText', TextareaType::class, [
                'label' => 'clearis.form.template.payment_terms_text',
                'help' => 'clearis.form.template.payment_terms_text_help',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('legalNotesText', TextareaType::class, [
                'label' => 'clearis.form.template.legal_notes_text',
                'help' => 'clearis.form.template.legal_notes_text_help',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('footerText', TextareaType::class, [
                'label' => 'clearis.form.template.footer_text',
                'help' => 'clearis.form.template.footer_text_help',
                'required' => false,
                'attr' => ['rows' => 2],
            ])

            // ----- Avanzado (developer-only) ---------------------------
            ->add('twigTemplate', TextType::class, [
                'label' => 'clearis.form.template.twig_template',
                'help' => 'clearis.form.template.twig_template_help',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceTemplate::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'clearis_invoicing_template';
    }
}
