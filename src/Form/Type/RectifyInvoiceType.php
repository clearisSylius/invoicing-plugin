<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Form\Type;

use ClearisSylius\InvoicingPlugin\Model\RectificationReasonEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * "Emitir rectificativa" admin form.
 *
 * Amount inputs are in cents (matching the entity convention) and only used
 * when `total = false`. For a total rectification the handler ignores the
 * deltas and negates the original.
 */
final class RectifyInvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'label' => 'clearis.form.rectify.reason',
                'choices' => array_combine(
                    array_values(RectificationReasonEnum::labels()),
                    array_keys(RectificationReasonEnum::labels()),
                ),
                'choice_translation_domain' => 'messages',
                'constraints' => [
                    new NotBlank(),
                    new Choice(choices: RectificationReasonEnum::all()),
                ],
            ])
            ->add('total', ChoiceType::class, [
                'label' => 'clearis.form.rectify.scope',
                'choices' => [
                    'clearis.form.rectify.scope_total' => true,
                    'clearis.form.rectify.scope_partial' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'data' => true,
            ])
            ->add('base_delta', IntegerType::class, [
                'label' => 'clearis.form.rectify.base_delta',
                'required' => false,
                'help' => 'clearis.form.rectify.base_delta_help',
            ])
            ->add('taxes_delta', IntegerType::class, [
                'label' => 'clearis.form.rectify.taxes_delta',
                'required' => false,
                'help' => 'clearis.form.rectify.taxes_delta_help',
            ])
        ;
    }
}
