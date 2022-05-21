<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DiskSpaceAlmostUsedSystemNotification extends SystemNotification
{
    public function isPermanent()
    {
        return false;
    }

    public function getTitle()
    {
        return '';
    }

    public function getBody()
    {
        $usage_percentage = AngieApplication::accountSettings()->getCurrentUsage()->getUsedDiskSpacePercent();

        $message = lang('Your storage is almost full (:percentage%)', ['percentage' => floor($usage_percentage)]);

        return $message;
    }

    public function getAction()
    {
        return AngieApplication::accountSettings()->getPricingModel()->isLegacy()
            ? lang('Switch to Per-Seat')
            : lang('Upgrade Storage');
    }

    public function getUrl()
    {
        return AngieApplication::accountSettings()->getPricingModel()->isLegacy()
            ? ROOT_URL . '/bundles/get-paid'
            : ROOT_URL . '/subscription/manage-add-ons/storage';
    }

    public function getSecondaryAction(): ?string
    {
        return AngieApplication::accountSettings()->getPricingModel()->isLegacy()
            ? lang('Upgrade plan')
            : parent::getSecondaryAction();
    }

    public function getSecondaryUrl(): ?string
    {
        return AngieApplication::accountSettings()->getPricingModel()->isLegacy()
            ? ROOT_URL . '/subscription/choose-plan'
            : parent::getSecondaryAction();
    }

    public function save()
    {
        $this->setAdditionalProperties([
            'rounded_percent_usage' => floor(AngieApplication::accountSettings()->getCurrentUsage()->getUsedDiskSpacePercent()),
        ]);

        return parent::save();
    }
}
