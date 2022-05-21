<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Model\FeaturePointer;

use AccountStatusInterface;
use ActiveCollab\Module\OnDemand\Model\AddOn\AddOnInterface;
use ActiveCollab\Module\OnDemand\Models\Pricing\PerSeat2018\PerSeat2018PricingModelInterface;
use AngieApplication;
use FeaturePointer;
use User;

class TrialAddOnsFeaturePointer extends FeaturePointer
{
    public function shouldShow(User $user): bool
    {
        $account_settings = AngieApplication::accountSettings();
        $status = $account_settings->getAccountStatus()->getStatus();
        $pricing_model = $account_settings->getPricingModel();

        if (!$pricing_model instanceof PerSeat2018PricingModelInterface) {
            return false;
        }

        if (!in_array($status, [AccountStatusInterface::STATUS_ACTIVE, AccountStatusInterface::STATUS_CANCELED])) {
            return false;
        }

        $add_ons_resolver = AngieApplication::addOnFinder();
        $paid_add_ons = $account_settings->getAddOns();

        $get_paid_full = $add_ons_resolver->getAddOn(AddOnInterface::ADD_ON_GET_PAID_FULL);

        foreach ($paid_add_ons as $paid_add_on) {
            if (in_array($paid_add_on, [AddOnInterface::ADD_ON_GET_PAID_FULL, AddOnInterface::ADD_ON_GET_PAID])) {
                return false;
            }
        }

        return $get_paid_full->canBeTried()
            && $user->isOwner()
            && parent::shouldShow($user);
    }

    public function getDescription(): string
    {
        if (!AngieApplication::isOnDemand()) {
            return '';
        }

        return lang('Try out the "Get Paid" bundle! Free for 30 days.');
    }
}
