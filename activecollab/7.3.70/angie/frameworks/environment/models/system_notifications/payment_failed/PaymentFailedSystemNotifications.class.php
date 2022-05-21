<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class PaymentFailedSystemNotifications extends SystemNotifications
{
    public static function getType()
    {
        return PaymentFailedSystemNotification::class;
    }

    public static function shouldBeRaised(): bool
    {
        if (AngieApplication::accountSettings()->getAccountStatus()->isFailedPayment()) {
            self::clearNotifications(); // remove notifications of this type and show the new ones

            return true;
        }

        return false;
    }

    public static function getAccountDaysUntilSuspension()
    {
        return (int) AngieApplication::accountSettings()->getAccountStatus()->getDaysToSuspension();
    }
}
