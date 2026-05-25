<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Controller\Admin;

use ClearisSylius\InvoicingPlugin\Doctrine\ORM\ChannelInvoicingSettingsRepository;
use ClearisSylius\InvoicingPlugin\Model\ChannelInvoicingSettingsInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Página landing del menú "Facturación → Configuración por canal".
 *
 * Lista todos los canales del shop con su estado actual de facturación:
 *   - sin configuración (todavía no se ha creado `ChannelInvoicingSettings`)
 *   - configurado parcialmente (existe la fila pero faltan serie estándar
 *     o datos del emisor — algo emitible pero potencialmente roto)
 *   - configurado completo (todo lo crítico relleno)
 *
 * Y por cada canal ofrece un botón "Configurar" / "Editar" que lleva al
 * formulario completo en `clearis_invoicing_admin_configure_channel`.
 *
 * Diseño: una sola query por canal a `ChannelInvoicingSettingsRepository`.
 * En instalaciones con <10 canales esto es trivial; si en algún futuro la
 * tienda escala a cientos, conviene N+1 a una sola query JOIN.
 */
final class ListChannelsForInvoicingController
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ChannelInvoicingSettingsRepository $settingsRepository,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(): Response
    {
        $channels = $this->channelRepository->findAll();

        $rows = [];
        foreach ($channels as $channel) {
            $settings = $this->settingsRepository->findByChannel($channel);

            $rows[] = [
                'channel' => $channel,
                'settings' => $settings,
                'status' => $this->resolveStatus($settings),
            ];
        }

        return new Response($this->twig->render(
            '@ClearisSyliusInvoicingPlugin/admin/channel/list_for_invoicing.html.twig',
            ['rows' => $rows],
        ));
    }

    /**
     * @return 'unconfigured'|'partial'|'configured'
     */
    private function resolveStatus(?ChannelInvoicingSettingsInterface $settings): string
    {
        if ($settings === null) {
            return 'unconfigured';
        }

        $hasStandardSeries = $settings->getStandardSeries() !== null;
        $hasShopBilling = $settings->getShopBillingData() !== null;

        if ($hasStandardSeries && $hasShopBilling) {
            return 'configured';
        }

        return 'partial';
    }
}
