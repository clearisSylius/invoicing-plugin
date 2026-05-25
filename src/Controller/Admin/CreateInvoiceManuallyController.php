<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Controller\Admin;

use ClearisSylius\InvoicingPlugin\Command\CreateInvoice;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Manual invoice issuance from the order admin.
 *
 * Errores de configuración (canal sin `ChannelInvoicingSettings`, falta
 * serie estándar, etc.) son recuperables — el admin solo necesita ir al
 * canal y configurar. NO queremos un 500 en esos casos. La estrategia:
 *
 *   1. Capturar `HandlerFailedException` (envoltorio que Messenger pone
 *      sobre cualquier excepción del handler en transporte síncrono).
 *   2. Sacar la causa raíz vía `getPrevious()` para inspeccionar tipo y
 *      mensaje.
 *   3. Mapear `RuntimeException`/`LogicException` (típicas de validación
 *      de pre-condiciones del factory) a un flash de error con el mensaje
 *      original — son siempre legibles porque los lanzamos nosotros.
 *   4. Cualquier otra excepción (BD caída, error inesperado) se re-lanza
 *      para que llegue al handler de errores de Symfony — esos sí deben
 *      ser 500 porque indican un fallo real, no de configuración.
 */
final class CreateInvoiceManuallyController
{
    /**
     * @param OrderRepositoryInterface<\Sylius\Component\Core\Model\OrderInterface> $orderRepository
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly MessageBusInterface $commandBus,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(Request $request, string $orderNumber): Response
    {
        $order = $this->orderRepository->findOneByNumber($orderNumber);
        if ($order === null) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $fallbackUrl = $this->urlGenerator->generate('sylius_admin_order_show', ['id' => $order->getId()]);
        $redirectUrl = $request->headers->get('referer') ?? $fallbackUrl;

        try {
            $this->commandBus->dispatch(new CreateInvoice($orderNumber));
        } catch (HandlerFailedException $exception) {
            $rootCause = $this->extractRootCause($exception);

            // Pre-condiciones del factory que el admin puede arreglar (canal
            // sin config, sin serie, etc.). Mostramos como flash y volvemos.
            if ($rootCause instanceof \RuntimeException || $rootCause instanceof \LogicException) {
                $this->logger->info(
                    'Manual invoice issuance blocked by configuration.',
                    ['order' => $orderNumber, 'reason' => $rootCause->getMessage()],
                );

                $this->flashBag($request)->add('error', $rootCause->getMessage());

                return new RedirectResponse($redirectUrl);
            }

            // Cualquier otra excepción (BD caída, fs sin permisos, etc.) es
            // un bug — propagamos para que aterrice en el handler estándar
            // y deje stacktrace en logs.
            throw $exception;
        }

        $this->flashBag($request)->add('success', 'clearis.invoice.flash.issued');

        return new RedirectResponse($redirectUrl);
    }

    /**
     * Symfony 6+ moved `getFlashBag()` out of `SessionInterface` into
     * `FlashBagAwareSessionInterface`. The default `Session` implementation
     * still implements both, so the assert is a no-op at runtime in the
     * Sylius admin context — it exists for the static analyser.
     */
    private function flashBag(Request $request): FlashBagInterface
    {
        $session = $request->getSession();
        \assert($session instanceof FlashBagAwareSessionInterface);

        return $session->getFlashBag();
    }

    /**
     * Saca la causa raíz de un HandlerFailedException. En Symfony 6.x usamos
     * `getPrevious()`; en 7.x deberíamos preferir `getWrappedExceptions()`,
     * pero `getPrevious()` sigue funcionando como fallback (Throwable). Si
     * Symfony decide quitarlo en una mayor, este método es el único sitio
     * que hay que tocar.
     */
    private function extractRootCause(HandlerFailedException $exception): \Throwable
    {
        return $exception->getPrevious() ?? $exception;
    }
}
