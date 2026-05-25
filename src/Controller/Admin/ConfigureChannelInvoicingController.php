<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Controller\Admin;

use ClearisSylius\InvoicingPlugin\Doctrine\ORM\ChannelInvoicingSettingsRepository;
use ClearisSylius\InvoicingPlugin\Entity\ChannelInvoicingSettings;
use ClearisSylius\InvoicingPlugin\Form\Type\ChannelInvoicingSettingsType;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Editor de `ChannelInvoicingSettings` por canal.
 *
 * Sylius admin no tiene una sección nativa para configurar este recurso
 * (vive 1:1 con el Channel). Este controller resuelve el bind por el
 * `channelId` de la URL, recupera (o crea) el ChannelInvoicingSettings,
 * lo edita con el form type estándar y persiste.
 *
 * URL: `/admin/channels/{channelId}/invoicing-settings`
 */
final class ConfigureChannelInvoicingController
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ChannelInvoicingSettingsRepository $settingsRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly FormFactoryInterface $formFactory,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $router,
    ) {
    }

    public function __invoke(Request $request, int $channelId): Response
    {
        $channel = $this->channelRepository->find($channelId);
        if (!$channel instanceof ChannelInterface) {
            throw new NotFoundHttpException(sprintf('Channel #%d not found.', $channelId));
        }

        $settings = $this->settingsRepository->findByChannel($channel);
        $isNew = $settings === null;
        if ($isNew) {
            $settings = new ChannelInvoicingSettings();
            $settings->setChannel($channel);
        }

        $form = $this->formFactory->create(ChannelInvoicingSettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $this->entityManager->persist($settings);
            }
            $this->entityManager->flush();

            return new RedirectResponse(
                $this->router->generate('clearis_invoicing_admin_configure_channel', [
                    'channelId' => $channelId,
                ]),
            );
        }

        return new Response($this->twig->render(
            '@ClearisSyliusInvoicingPlugin/admin/channel/configure_invoicing.html.twig',
            [
                'form' => $form->createView(),
                'channel' => $channel,
                'settings' => $settings,
                'is_new' => $isNew,
            ],
        ));
    }
}
