<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;

abstract class InvoiceNotification extends Notification
{
    /**
     * Return visit URL.
     *
     * @return string
     */
    public function getVisitUrl(IUser $user)
    {
        $parent = $this->getParent();

        return $parent instanceof Invoice ? $parent->getPublicUrl() : '#';
    }

    /**
     * Return files attached to this notification, if any.
     *
     * @return array
     */
    public function getAttachments(NotificationChannel $channel)
    {
        $parent = $this->getParent();

        return $parent instanceof Invoice ? [$parent->exportToFile() => Invoices::getInvoicePdfName($parent)] : null;
    }

    public function ignoreSender(): bool
    {
        return false;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'owner_company' => AngieApplication::getContainer()
                ->get(OwnerCompanyResolverInterface::class)
                    ->getCompany(),
        ];
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof WebInterfaceNotificationChannel) {
            return false; // Users should see no notifications about invoices in the web interface
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
