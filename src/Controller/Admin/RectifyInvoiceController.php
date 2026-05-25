<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Controller\Admin;

use ClearisSylius\InvoicingPlugin\Command\RectifyInvoice;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceRepositoryInterface;
use ClearisSylius\InvoicingPlugin\Form\Type\RectifyInvoiceType;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Form de emisión de factura rectificativa.
 *
 * Estrategia de manejo de errores idéntica a CreateInvoiceManuallyController:
 * los errores de pre-condición (canal sin serie rectificativa, motivo no
 * válido, factura ya cancelada, etc.) se mapean a flash de error y volvemos
 * al show de la factura. Los errores inesperados se propagan al handler
 * estándar para que generen 500 y queden trazados.
 */
final class RectifyInvoiceController
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository,
        private readonly FormFactoryInterface $formFactory,
        private readonly MessageBusInterface $commandBus,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $invoice = $this->repository->find($id);
        if ($invoice === null) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $form = $this->formFactory->create(RectifyInvoiceType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // El `reason` se restringe al union literal del enum aquí mismo
            // porque la garantía viene del form (ChoiceType con los 5 valores
            // de RectificationReasonEnum). PHPStan no puede seguir esa pista,
            // así que afirmamos el shape del array.
            /** @var array{reason: 'R1'|'R2'|'R3'|'R4'|'R5', total: bool, base_delta: int, taxes_delta: int} $data */
            $data = $form->getData();

            try {
                $this->commandBus->dispatch(new RectifyInvoice(
                    originalInvoiceId: $id,
                    reason: $data['reason'],
                    isTotal: $data['total'],
                    baseDelta: $data['base_delta'],
                    taxesDelta: $data['taxes_delta'],
                ));
            } catch (HandlerFailedException $exception) {
                $rootCause = $exception->getPrevious() ?? $exception;

                // `\InvalidArgumentException` extiende `\LogicException`, así
                // que un check explícito aquí es redundante (y PHPStan max lo
                // marca como inalcanzable después del segundo `instanceof`).
                if ($rootCause instanceof \RuntimeException ||
                    $rootCause instanceof \LogicException
                ) {
                    $this->logger->info(
                        'Invoice rectification blocked by configuration or invariant.',
                        ['invoice' => $id, 'reason' => $rootCause->getMessage()],
                    );

                    $this->flashBag($request)->add('error', $rootCause->getMessage());

                    return new RedirectResponse($this->urlGenerator->generate(
                        'clearis_invoicing_admin_invoice_show',
                        ['id' => $id],
                    ));
                }

                throw $exception;
            }

            $this->flashBag($request)->add('success', 'clearis.invoice.flash.rectified');

            return new RedirectResponse($this->urlGenerator->generate('clearis_invoicing_admin_invoice_show', ['id' => $id]));
        }

        return new Response($this->twig->render(
            '@ClearisSyliusInvoicingPlugin/admin/invoice/rectify.html.twig',
            [
                'invoice' => $invoice,
                'form' => $form->createView(),
            ],
        ));
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
}
