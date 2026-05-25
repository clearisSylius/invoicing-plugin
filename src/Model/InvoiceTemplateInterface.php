<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * Configuración de plantilla PDF — define el branding y los toggles de
 * presentación opcionales. El contenido legalmente obligatorio NO es
 * configurable a este nivel (ver `Entity\InvoiceTemplate` para el detalle).
 */
interface InvoiceTemplateInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getCode(): string;

    public function setCode(string $code): void;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    public function getTwigTemplate(): string;

    public function setTwigTemplate(string $twigTemplate): void;

    public function getLogoPath(): ?string;

    public function setLogoPath(?string $logoPath): void;

    /** @return InvoiceTypeEnum::* */
    public function getType(): string;

    public function setType(string $type): void;

    // --- Branding ---
    public function getAccentColor(): string;

    public function setAccentColor(string $accentColor): void;

    public function getLayoutDensity(): string;

    public function setLayoutDensity(string $layoutDensity): void;

    public function isShowLogo(): bool;

    public function setShowLogo(bool $showLogo): void;

    // --- Cabecera ---
    public function getHeaderText(): ?string;

    public function setHeaderText(?string $headerText): void;

    public function getHeaderContactInfo(): ?string;

    public function setHeaderContactInfo(?string $headerContactInfo): void;

    // --- Cliente ---
    public function isShowCustomerEmail(): bool;

    public function setShowCustomerEmail(bool $showCustomerEmail): void;

    public function isShowCustomerShippingAddress(): bool;

    public function setShowCustomerShippingAddress(bool $showCustomerShippingAddress): void;

    // --- Líneas ---
    public function isShowLineSku(): bool;

    public function setShowLineSku(bool $showLineSku): void;

    public function isShowLineExtendedDescription(): bool;

    public function setShowLineExtendedDescription(bool $showLineExtendedDescription): void;

    public function isShowLineDiscounts(): bool;

    public function setShowLineDiscounts(bool $showLineDiscounts): void;

    // --- Totales ---
    public function isShowTaxBreakdown(): bool;

    public function setShowTaxBreakdown(bool $showTaxBreakdown): void;

    // --- Referencias del pedido ---
    public function isShowOrderNumber(): bool;

    public function setShowOrderNumber(bool $showOrderNumber): void;

    public function isShowPaymentMethod(): bool;

    public function setShowPaymentMethod(bool $showPaymentMethod): void;

    public function isShowShippingMethod(): bool;

    public function setShowShippingMethod(bool $showShippingMethod): void;

    // --- Textos libres ---
    public function getPaymentTermsText(): ?string;

    public function setPaymentTermsText(?string $paymentTermsText): void;

    public function getLegalNotesText(): ?string;

    public function setLegalNotesText(?string $legalNotesText): void;

    public function getFooterText(): ?string;

    public function setFooterText(?string $footerText): void;
}
