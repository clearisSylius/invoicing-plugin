<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\DependencyInjection;

use ClearisSylius\InvoicingPlugin\Doctrine\ORM\ChannelInvoicingSettingsRepository;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceRepository;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceSeriesRepository;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceTemplateRepository;
use ClearisSylius\InvoicingPlugin\Entity\BillingData;
use ClearisSylius\InvoicingPlugin\Entity\ChannelInvoicingSettings;
use ClearisSylius\InvoicingPlugin\Entity\Invoice;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceLineItem;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceSeries;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceTaxItem;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceTemplate;
use ClearisSylius\InvoicingPlugin\Entity\ShopBillingData;
use ClearisSylius\InvoicingPlugin\Form\Type\ChannelInvoicingSettingsType;
use ClearisSylius\InvoicingPlugin\Form\Type\InvoiceSeriesType;
use ClearisSylius\InvoicingPlugin\Form\Type\InvoiceTemplateType;
use ClearisSylius\InvoicingPlugin\Model\BillingDataInterface;
use ClearisSylius\InvoicingPlugin\Model\ChannelInvoicingSettingsInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceLineItemInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceSeriesInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTaxItemInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTemplateInterface;
use ClearisSylius\InvoicingPlugin\Model\ShopBillingDataInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('clearis_sylius_invoicing');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('driver')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')->defaultValue('doctrine/orm')->end()
                    ->end()
                ->end()

                ->booleanNode('legacy_mode')
                    ->defaultFalse()
                    ->info(
                        'When true, the plugin\'s order/payment/shipment listeners do NOT fire. '
                        . 'This is the safe state during installation while sylius/invoicing-plugin is still '
                        . 'the active invoicing system. Set to false at cutover time.',
                    )
                ->end()

                ->arrayNode('pdf')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_template_code')
                            ->defaultValue('default')
                            ->info('Code of the InvoiceTemplate used as a fallback when a channel has no template assigned.')
                        ->end()
                        ->scalarNode('storage_directory')
                            ->defaultValue('%kernel.project_dir%/var/invoices')
                            ->info('Absolute path where generated PDFs are written.')
                        ->end()
                        ->scalarNode('paper_size')->defaultValue('A4')->end()
                        ->enumNode('orientation')
                            ->values(['portrait', 'landscape'])
                            ->defaultValue('portrait')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('admin')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('invoice_path')
                            ->defaultValue('clearis-invoices')
                            ->info('URL segment under /admin/. Default avoids collision with the official plugin which uses /admin/invoices/.')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('email')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('sender_address')
                            ->defaultNull()
                            ->info('Fallback `From:` address used when a channel does not set its own. If both are null, invoice emails are skipped silently (the invoice still gets issued).')
                        ->end()
                        ->scalarNode('sender_name')
                            ->defaultValue('Facturación')
                            ->info('Fallback `From:` display name used alongside `sender_address`.')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->append($this->createResourceNode('invoice', Invoice::class, InvoiceInterface::class, InvoiceRepository::class))
                        ->append($this->createResourceNode('invoice_line_item', InvoiceLineItem::class, InvoiceLineItemInterface::class))
                        ->append($this->createResourceNode('invoice_tax_item', InvoiceTaxItem::class, InvoiceTaxItemInterface::class))
                        ->append($this->createResourceNode('billing_data', BillingData::class, BillingDataInterface::class))
                        ->append($this->createResourceNode('shop_billing_data', ShopBillingData::class, ShopBillingDataInterface::class))
                        // `form` es obligatorio para que el CRUD de Sylius
                        // pueda resolver el FormType en create/update — sin
                        // él lanza "Class 'form' is not configured for
                        // resource ...". Los demás recursos no tienen
                        // CRUD admin propio así que no necesitan form.
                        ->append($this->createResourceNode('invoice_series', InvoiceSeries::class, InvoiceSeriesInterface::class, InvoiceSeriesRepository::class, InvoiceSeriesType::class))
                        ->append($this->createResourceNode('invoice_template', InvoiceTemplate::class, InvoiceTemplateInterface::class, InvoiceTemplateRepository::class, InvoiceTemplateType::class))
                        ->append($this->createResourceNode('channel_invoicing_settings', ChannelInvoicingSettings::class, ChannelInvoicingSettingsInterface::class, ChannelInvoicingSettingsRepository::class, ChannelInvoicingSettingsType::class))
                        // NOTE: NO registramos `address` como recurso propio aquí.
                        //       Address se sobreescribe vía `sylius_addressing.resources.address.classes.model`
                        //       en ClearisSyliusInvoicingExtension::prependAddressResourceOverride().
                        //       Declararla aquí duplicaba la entidad en el resolver y
                        //       contribuía al error "Class 'Sylius\Component\Addressing\Model\AddressInterface'
                        //       does not exist" al renderizar latest_statistics.
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function createResourceNode(
        string $name,
        string $model,
        string $interface,
        ?string $repository = null,
        ?string $form = null,
    ): \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition {
        $builder = new TreeBuilder($name);
        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node */
        $node = $builder->getRootNode();
        $classes = $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('classes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('model')->defaultValue($model)->end()
                        ->scalarNode('interface')->defaultValue($interface)->end()
                        ->scalarNode('controller')->defaultValue(ResourceController::class)->end()
                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
        ;

        if ($repository !== null) {
            $classes->scalarNode('repository')->defaultValue($repository)->end();
        }

        if ($form !== null) {
            $classes->scalarNode('form')->defaultValue($form)->end();
        }

        $classes
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
