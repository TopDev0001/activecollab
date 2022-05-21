<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;

/**
 * Estimate notification.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage notifications
 */
abstract class EstimateNotification extends Notification
{
    /**
     * Return visit URL.
     *
     * @return string
     */
    public function getVisitUrl(IUser $user)
    {
        $parent = $this->getParent();

        return $parent instanceof Estimate ? $parent->getPublicUrl() : '#';
    }

    /**
     * Return files attached to this notification, if any.
     *
     * @return array
     */
    public function getAttachments(NotificationChannel $channel)
    {
        $parent = $this->getParent();

        return $parent instanceof Estimate ? [$parent->exportToFile() => Estimates::getEstimatePdfName($parent)] : null;
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
            return false; // Clients should see no notifications about invoices in the web interface
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
