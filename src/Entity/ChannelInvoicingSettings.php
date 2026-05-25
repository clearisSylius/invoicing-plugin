<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Entity;

use ClearisSylius\InvoicingPlugin\Model\ChannelInvoicingSettingsInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceSeriesInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTemplateInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTriggerEnum;
use ClearisSylius\InvoicingPlugin\Model\ShopBillingDataInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

class ChannelInvoicingSettings implements ChannelInvoicingSettingsInterface
{
    protected ?int $id = null;

    protected ChannelInterface $channel;

    /** @phpstan-var InvoiceTriggerEnum::* */
    protected string $trigger = InvoiceTriggerEnum::ON_PAYMENT_COMPLETED;

    protected ?InvoiceTemplateInterface $standardTemplate = null;

    protected ?InvoiceTemplateInterface $rectifyingTemplate = null;

    protected ?InvoiceSeriesInterface $standardSeries = null;

    protected ?InvoiceSeriesInterface $rectifyingSeries = null;

    protected bool $sendEmailOnIssue = true;

    /**
     * Dirección desde la que se envía el email de factura al cliente (campo
     * From:). Si está vacío, se usa el fallback global definido en
     * `%clearis_sylius_invoicing.email.sender_address%`. Si tampoco hay
     * fallback, el email no se manda (silenciosamente, no se rompe la
     * emisión de la factura).
     */
    protected ?string $senderEmail = null;

    /** Nombre humano para el From: (acompaña al email). */
    protected ?string $senderName = null;

    protected ?ShopBillingDataInterface $shopBillingData = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getTrigger(): string
    {
        return $this->trigger;
    }

    /**
     * @phpstan-param InvoiceTriggerEnum::* $trigger
     */
    public function setTrigger(string $trigger): void
    {
        $this->trigger = $trigger;
    }

    public function getStandardTemplate(): ?InvoiceTemplateInterface
    {
        return $this->standardTemplate;
    }

    public function setStandardTemplate(?InvoiceTemplateInterface $template): void
    {
        $this->standardTemplate = $template;
    }

    public function getRectifyingTemplate(): ?InvoiceTemplateInterface
    {
        return $this->rectifyingTemplate;
    }

    public function setRectifyingTemplate(?InvoiceTemplateInterface $template): void
    {
        $this->rectifyingTemplate = $template;
    }

    public function getStandardSeries(): ?InvoiceSeriesInterface
    {
        return $this->standardSeries;
    }

    public function setStandardSeries(?InvoiceSeriesInterface $series): void
    {
        $this->standardSeries = $series;
    }

    public function getRectifyingSeries(): ?InvoiceSeriesInterface
    {
        return $this->rectifyingSeries;
    }

    public function setRectifyingSeries(?InvoiceSeriesInterface $series): void
    {
        $this->rectifyingSeries = $series;
    }

    public function isSendEmailOnIssue(): bool
    {
        return $this->sendEmailOnIssue;
    }

    public function setSendEmailOnIssue(bool $send): void
    {
        $this->sendEmailOnIssue = $send;
    }

    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(?string $senderEmail): void
    {
        $this->senderEmail = $senderEmail;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(?string $senderName): void
    {
        $this->senderName = $senderName;
    }

    public function getShopBillingData(): ?ShopBillingDataInterface
    {
        return $this->shopBillingData;
    }

    public function setShopBillingData(?ShopBillingDataInterface $shopBillingData): void
    {
        $this->shopBillingData = $shopBillingData;
    }
}
