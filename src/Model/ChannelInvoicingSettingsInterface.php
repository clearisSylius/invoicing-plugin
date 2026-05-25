<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * Per-channel invoicing configuration. Modelled as a sibling entity to
 * Channel (1:1) so we don't have to touch Sylius core's Channel mapping or
 * decorate that entity.
 */
interface ChannelInvoicingSettingsInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getChannel(): ChannelInterface;

    public function setChannel(ChannelInterface $channel): void;

    /** @return InvoiceTriggerEnum::* */
    public function getTrigger(): string;

    public function setTrigger(string $trigger): void;

    public function getStandardTemplate(): ?InvoiceTemplateInterface;

    public function setStandardTemplate(?InvoiceTemplateInterface $template): void;

    public function getRectifyingTemplate(): ?InvoiceTemplateInterface;

    public function setRectifyingTemplate(?InvoiceTemplateInterface $template): void;

    public function getStandardSeries(): ?InvoiceSeriesInterface;

    public function setStandardSeries(?InvoiceSeriesInterface $series): void;

    public function getRectifyingSeries(): ?InvoiceSeriesInterface;

    public function setRectifyingSeries(?InvoiceSeriesInterface $series): void;

    public function isSendEmailOnIssue(): bool;

    public function setSendEmailOnIssue(bool $send): void;

    public function getSenderEmail(): ?string;

    public function setSenderEmail(?string $senderEmail): void;

    public function getSenderName(): ?string;

    public function setSenderName(?string $senderName): void;

    public function getShopBillingData(): ?ShopBillingDataInterface;

    public function setShopBillingData(?ShopBillingDataInterface $shopBillingData): void;
}
