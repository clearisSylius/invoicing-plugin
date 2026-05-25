<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\DependencyInjection;

use ClearisSylius\InvoicingPlugin\Entity\Address;
use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * Plugin extension.
 *
 * Loads services, wires Doctrine mappings, prepends the resource overrides
 * for `sylius_addressing.address` (so every Sylius Address carries a `taxId`
 * column), and registers our migrations namespace.
 */
final class ClearisSyliusInvoicingExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    /**
     * Locked explicitly to `clearis_sylius_invoicing` so the configuration key
     * (and every container parameter / service tag derived from it) stays
     * stable regardless of future bundle-class renames. Symfony would
     * auto-derive the same value from the current class name, but pinning it
     * here protects existing installations from breaking on a rebrand.
     */
    public function getAlias(): string
    {
        return 'clearis_sylius_invoicing';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('clearis_sylius_invoicing.legacy_mode', $config['legacy_mode']);
        $container->setParameter('clearis_sylius_invoicing.pdf.default_template_code', $config['pdf']['default_template_code']);
        $container->setParameter('clearis_sylius_invoicing.pdf.storage_directory', $config['pdf']['storage_directory']);
        $container->setParameter('clearis_sylius_invoicing.pdf.paper_size', $config['pdf']['paper_size']);
        $container->setParameter('clearis_sylius_invoicing.pdf.orientation', $config['pdf']['orientation']);
        $container->setParameter('clearis_sylius_invoicing.admin.invoice_path', $config['admin']['invoice_path']);
        $container->setParameter('clearis_sylius_invoicing.email.sender_address', $config['email']['sender_address']);
        $container->setParameter('clearis_sylius_invoicing.email.sender_name', $config['email']['sender_name']);

        $this->registerResources('clearis_invoicing', $config['driver']['name'], $config['resources'], $container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);
        $this->prependDoctrineMapping($container);
        $this->prependAddressResourceOverride($container);
        $this->prependTwigHooks($container);
        $this->prependWorkflow($container);
        $this->prependGrids($container);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Clearis\\SyliusInvoicingPlugin\\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@ClearisSyliusInvoicingPlugin/src/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return ['Sylius\\Bundle\\CoreBundle\\Migrations'];
    }

    private function prependDoctrineMapping(ContainerBuilder $container): void
    {
        $mappingDir = realpath(__DIR__ . '/../../config/doctrine');
        if ($mappingDir === false) {
            return;
        }

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'ClearisSyliusInvoicingPlugin' => [
                        'type' => 'xml',
                        'dir' => $mappingDir,
                        'prefix' => 'Clearis\\SyliusInvoicingPlugin\\Entity',
                        'alias' => 'ClearisSyliusInvoicingPlugin',
                        'is_bundle' => false,
                    ],
                ],
            ],
        ]);
    }

    private function prependAddressResourceOverride(ContainerBuilder $container): void
    {
        // IMPORTANTE: solo sobreescribimos `model`, NO `interface`.
        //
        // Si tocamos `interface`, Sylius reemplaza en el mapa
        // `doctrine.orm.resolve_target_entities` la entrada original
        // `Sylius\Component\Addressing\Model\AddressInterface → Address`
        // por una entrada para nuestra interfaz custom — dejando huérfana
        // la base. Cualquier mapeo Doctrine que apunte a la interfaz
        // base (por ejemplo el `billingAddress` de Order que tira el
        // dashboard al renderizar `latest_statistics`) revienta con
        // "Class 'Sylius\Component\Addressing\Model\AddressInterface'
        // does not exist". Manteniendo `interface` por defecto se
        // preserva la cadena Addressing → Core → nuestra Address.
        $container->prependExtensionConfig('sylius_addressing', [
            'resources' => [
                'address' => [
                    'classes' => [
                        'model' => Address::class,
                    ],
                ],
            ],
        ]);
    }

    private function prependTwigHooks(ContainerBuilder $container): void
    {
        $hooksFile = __DIR__ . '/../../config/twig_hooks/admin.php';
        if (!is_file($hooksFile)) {
            return;
        }

        /** @var array<string, mixed> $hooks */
        $hooks = require $hooksFile;

        $container->prependExtensionConfig('sylius_twig_hooks', [
            'hooks' => $hooks,
        ]);
    }

    private function prependWorkflow(ContainerBuilder $container): void
    {
        $workflowFile = __DIR__ . '/../../config/workflow/invoice.php';
        if (!is_file($workflowFile)) {
            return;
        }

        /** @var array<string, mixed> $workflow */
        $workflow = require $workflowFile;

        $container->prependExtensionConfig('framework', [
            'workflows' => $workflow,
        ]);
    }

    /**
     * Loads grid definitions from `config/grids/*.yaml` and prepends them
     * to the `sylius_grid` extension config.
     *
     * Why this is needed: this plugin doesn't load `config/config.yaml` as
     * the entry point (only `services.xml` is loaded in `load()`), so the
     * Symfony imports inside `config.yaml` never run. Grids live exclusively
     * in YAML, so without this prepend, `sylius_grid` would never see
     * `clearis_invoicing_invoice`, `clearis_invoicing_series` or
     * `clearis_invoicing_template`, and the admin index controller would
     * throw `UndefinedGridException`.
     *
     * Each YAML file is expected to be a standard Sylius grid definition
     * with a top-level `sylius_grid:` key.
     */
    private function prependGrids(ContainerBuilder $container): void
    {
        $gridsDir = __DIR__ . '/../../config/grids';
        if (!is_dir($gridsDir)) {
            return;
        }

        $files = glob($gridsDir . '/*.yaml') ?: [];
        sort($files);

        foreach ($files as $file) {
            /** @var array<string, mixed> $parsed */
            $parsed = Yaml::parseFile($file);

            if (!isset($parsed['sylius_grid']) || !is_array($parsed['sylius_grid'])) {
                continue;
            }

            $container->prependExtensionConfig('sylius_grid', $parsed['sylius_grid']);
        }
    }
}
