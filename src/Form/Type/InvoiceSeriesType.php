<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Form\Type;

use ClearisSylius\InvoicingPlugin\Entity\InvoiceSeries;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTypeEnum;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InvoiceSeriesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'clearis.form.series.code',
            ])
            ->add('name', TextType::class, [
                'label' => 'clearis.form.series.name',
                'required' => false,
            ])
            ->add('channel', ChannelChoiceType::class, [
                'label' => 'clearis.form.series.channel',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'clearis.form.series.type',
                'choices' => [
                    'clearis.form.series.type_standard' => InvoiceTypeEnum::STANDARD,
                    'clearis.form.series.type_rectifying' => InvoiceTypeEnum::RECTIFYING,
                ],
            ])
            ->add('format', TextType::class, [
                'label' => 'clearis.form.series.format',
                'help' => 'clearis.form.series.format_help',
            ])
            ->add('padding', IntegerType::class, [
                'label' => 'clearis.form.series.padding',
            ])
            ->add('currentNumber', IntegerType::class, [
                'label' => 'clearis.form.series.current_number',
                'help' => 'clearis.form.series.current_number_help',
            ])
            ->add('yearlyReset', CheckboxType::class, [
                'label' => 'clearis.form.series.yearly_reset',
                'required' => false,
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'clearis.form.series.active',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceSeries::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'clearis_invoicing_series';
    }
}
