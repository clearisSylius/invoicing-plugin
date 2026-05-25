<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Entity;

use ClearisSylius\InvoicingPlugin\Model\InvoiceTemplateInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTypeEnum;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * Configuración por plantilla de factura.
 *
 * Cada `InvoiceTemplate` define cómo se RENDERIZA un PDF: qué bloques se
 * muestran, qué textos libres se intercalan y qué branding aplicar. El
 * contenido legalmente obligatorio (razón social y NIF emisor/cliente,
 * dirección fiscal, base por tipo de IVA, cuota IVA, total) NUNCA es
 * ocultable — los toggles aquí controlan solo elementos opcionales.
 *
 * Las plantillas se asocian por (canal, tipo) en `ChannelInvoicingSettings`.
 */
class InvoiceTemplate implements InvoiceTemplateInterface
{
    protected ?int $id = null;

    protected string $code;

    protected ?string $name = null;

    protected ?ChannelInterface $channel = null;

    /**
     * Ruta del .twig que renderiza el PDF. Por defecto apunta a la
     * plantilla base del plugin, que ya lee todos los toggles de abajo.
     * Si un dev override la plantilla, su .twig recibe el mismo `template`
     * y puede consultar los mismos getters.
     */
    protected string $twigTemplate = '@ClearisSyliusInvoicingPlugin/pdf/default.html.twig';

    /** Path absoluto o relativo al kernel. Si se vacía o `showLogo=false`, no se pinta. */
    protected ?string $logoPath = null;

    /** @phpstan-var InvoiceTypeEnum::* */
    protected string $type = InvoiceTypeEnum::STANDARD;

    // ----- Branding -----------------------------------------------------

    /** Hex color (e.g. "#2a8d4a") usado como tinta de cabeceras y totales. */
    protected string $accentColor = '#222222';

    /** Densidad visual: compact | regular | spacious. Mapea a paddings y márgenes. */
    protected string $layoutDensity = 'regular';

    protected bool $showLogo = true;

    // ----- Cabecera -----------------------------------------------------

    /**
     * Texto libre que se pinta SOBRE la dirección del emisor en la cabecera.
     * Útil para slogan, tagline, "Factura simplificada", etc.
     */
    protected ?string $headerText = null;

    /**
     * Texto libre con información de contacto del emisor que aparece bajo
     * la dirección fiscal. Conviene para teléfono, email, web, etc. — datos
     * que no viven en `ShopBillingData` (que solo guarda los fiscales).
     */
    protected ?string $headerContactInfo = null;

    // ----- Cliente ------------------------------------------------------

    /** Mostrar email del cliente (si el pedido lo tiene). */
    protected bool $showCustomerEmail = false;

    /** Mostrar también dirección de envío además de la de facturación. */
    protected bool $showCustomerShippingAddress = false;

    // ----- Líneas -------------------------------------------------------

    /** Mostrar columna con el código de variante (SKU). */
    protected bool $showLineSku = false;

    /** Mostrar descripción extendida (variantName) como subrayado bajo el name. */
    protected bool $showLineExtendedDescription = true;

    /** Mostrar columna de descuento por línea (si hay). */
    protected bool $showLineDiscounts = false;

    // ----- Totales / IVA ------------------------------------------------

    /** Mostrar tabla con desglose por tipo de IVA bajo el subtotal. */
    protected bool $showTaxBreakdown = true;

    // ----- Referencias del pedido --------------------------------------

    /** Mostrar el número interno del pedido junto al número de factura. */
    protected bool $showOrderNumber = true;

    /** Mostrar el método de pago utilizado. */
    protected bool $showPaymentMethod = false;

    /** Mostrar el método de envío y tracking si existe. */
    protected bool $showShippingMethod = false;

    // ----- Bloques de texto libres -------------------------------------

    /** Condiciones de pago (vencimiento, intereses de demora, etc.). */
    protected ?string $paymentTermsText = null;

    /** Notas legales (LOPD, garantías, leyendas obligatorias por sector, etc.). */
    protected ?string $legalNotesText = null;

    /** Pie de página (centrado, fuente reducida). */
    protected ?string $footerText = null;

    // ===================================================================
    // Getters / setters
    // ===================================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getTwigTemplate(): string
    {
        return $this->twigTemplate;
    }

    public function setTwigTemplate(string $twigTemplate): void
    {
        $this->twigTemplate = $twigTemplate;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): void
    {
        $this->logoPath = $logoPath;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @phpstan-param InvoiceTypeEnum::* $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getAccentColor(): string
    {
        return $this->accentColor;
    }

    public function setAccentColor(string $accentColor): void
    {
        $this->accentColor = $accentColor;
    }

    public function getLayoutDensity(): string
    {
        return $this->layoutDensity;
    }

    public function setLayoutDensity(string $layoutDensity): void
    {
        $this->layoutDensity = $layoutDensity;
    }

    public function isShowLogo(): bool
    {
        return $this->showLogo;
    }

    public function setShowLogo(bool $showLogo): void
    {
        $this->showLogo = $showLogo;
    }

    public function getHeaderText(): ?string
    {
        return $this->headerText;
    }

    public function setHeaderText(?string $headerText): void
    {
        $this->headerText = $headerText;
    }

    public function getHeaderContactInfo(): ?string
    {
        return $this->headerContactInfo;
    }

    public function setHeaderContactInfo(?string $headerContactInfo): void
    {
        $this->headerContactInfo = $headerContactInfo;
    }

    public function isShowCustomerEmail(): bool
    {
        return $this->showCustomerEmail;
    }

    public function setShowCustomerEmail(bool $showCustomerEmail): void
    {
        $this->showCustomerEmail = $showCustomerEmail;
    }

    public function isShowCustomerShippingAddress(): bool
    {
        return $this->showCustomerShippingAddress;
    }

    public function setShowCustomerShippingAddress(bool $showCustomerShippingAddress): void
    {
        $this->showCustomerShippingAddress = $showCustomerShippingAddress;
    }

    public function isShowLineSku(): bool
    {
        return $this->showLineSku;
    }

    public function setShowLineSku(bool $showLineSku): void
    {
        $this->showLineSku = $showLineSku;
    }

    public function isShowLineExtendedDescription(): bool
    {
        return $this->showLineExtendedDescription;
    }

    public function setShowLineExtendedDescription(bool $showLineExtendedDescription): void
    {
        $this->showLineExtendedDescription = $showLineExtendedDescription;
    }

    public function isShowLineDiscounts(): bool
    {
        return $this->showLineDiscounts;
    }

    public function setShowLineDiscounts(bool $showLineDiscounts): void
    {
        $this->showLineDiscounts = $showLineDiscounts;
    }

    public function isShowTaxBreakdown(): bool
    {
        return $this->showTaxBreakdown;
    }

    public function setShowTaxBreakdown(bool $showTaxBreakdown): void
    {
        $this->showTaxBreakdown = $showTaxBreakdown;
    }

    public function isShowOrderNumber(): bool
    {
        return $this->showOrderNumber;
    }

    public function setShowOrderNumber(bool $showOrderNumber): void
    {
        $this->showOrderNumber = $showOrderNumber;
    }

    public function isShowPaymentMethod(): bool
    {
        return $this->showPaymentMethod;
    }

    public function setShowPaymentMethod(bool $showPaymentMethod): void
    {
        $this->showPaymentMethod = $showPaymentMethod;
    }

    public function isShowShippingMethod(): bool
    {
        return $this->showShippingMethod;
    }

    public function setShowShippingMethod(bool $showShippingMethod): void
    {
        $this->showShippingMethod = $showShippingMethod;
    }

    public function getPaymentTermsText(): ?string
    {
        return $this->paymentTermsText;
    }

    public function setPaymentTermsText(?string $paymentTermsText): void
    {
        $this->paymentTermsText = $paymentTermsText;
    }

    public function getLegalNotesText(): ?string
    {
        return $this->legalNotesText;
    }

    public function setLegalNotesText(?string $legalNotesText): void
    {
        $this->legalNotesText = $legalNotesText;
    }

    public function getFooterText(): ?string
    {
        return $this->footerText;
    }

    public function setFooterText(?string $footerText): void
    {
        $this->footerText = $footerText;
    }
}
