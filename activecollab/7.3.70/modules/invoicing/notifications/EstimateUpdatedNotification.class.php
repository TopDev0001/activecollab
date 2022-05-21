<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

/**
 * Estimate updated notification.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage notifications
 */
class EstimateUpdatedNotification extends EstimateNotification
{
    /**
     * Set old total.
     *
     * @param  float $value
     * @return $this
     */
    public function &setOldTotal($value)
    {
        $this->setAdditionalProperty('old_total', $value);

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return array_merge(
            parent::getAdditionalTemplateVars($channel),
            [
                'old_total' => $this->getOldTotal(),
            ]
        );
    }

    /**
     * Get old total.
     *
     * @return float
     */
    public function getOldTotal()
    {
        return $this->getAdditionalProperty('old_total');
    }

    /**
     * Return attachments.
     *
     * @return array
     */
    public function getAttachments(NotificationChannel $channel)
    {
        if ($channel instanceof EmailNotificationChannel) {
            $parent = $this->getParent();

            if ($parent instanceof Estimate) {
                return [$parent->exportToFile() => Estimates::getEstimatePdfName($parent)];
            }
        }

        return parent::getAttachments($channel);
    }

    public function ignoreSender(): bool
    {
        return false;
    }
}
