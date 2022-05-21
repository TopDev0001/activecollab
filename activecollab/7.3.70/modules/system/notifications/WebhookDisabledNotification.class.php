<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPoolInterface;

class WebhookDisabledNotification extends Notification
{
    public function getWebhooksUrl()
    {
        return $this->getAdditionalProperty('webhooks_url');
    }

    public function &setWebhooksUrl(string $webhooks_url): WebhookDisabledNotification
    {
        $this->setAdditionalProperty('webhooks_url', $webhooks_url);

        return $this;
    }

    public function getWebhook()
    {
        return AngieApplication::getContainer()
            ->get(DataObjectPoolInterface::class)
                ->get(Webhook::class, $this->getAdditionalProperty('webhook_id'));
    }

    public function &setWebhook(Webhook $webhook): WebhookDisabledNotification
    {
        $this->setAdditionalProperty('webhook_id', $webhook->getId());

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'webhooks_url' => $this->getWebhooksUrl(),
            'webhook' => $this->getWebhook(),
        ];
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof EmailNotificationChannel) {
            return true;
        }

        if ($channel instanceof WebInterfaceNotificationChannel) {
            return false;
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
