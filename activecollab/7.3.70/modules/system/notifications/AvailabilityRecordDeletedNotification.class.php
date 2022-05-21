<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class AvailabilityRecordDeletedNotification extends Notification
{
    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        /** @var AvailabilityRecord $availability_record */
        $availability_record = AvailabilityRecords::findById($this->getParentId());
        $availability_type = $availability_record->getAvailabilityType();

        return array_merge(
            parent::getAdditionalTemplateVars($channel),
            [
                'availability_type' => $availability_type,
                'availability_record' => $availability_record,
            ]
        );
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof EmailNotificationChannel) {
            return true;
        } elseif ($channel instanceof WebInterfaceNotificationChannel) {
            return false;
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
