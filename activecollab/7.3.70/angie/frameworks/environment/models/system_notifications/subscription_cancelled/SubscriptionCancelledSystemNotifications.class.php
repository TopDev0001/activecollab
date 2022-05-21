<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class SubscriptionCancelledSystemNotifications extends SystemNotifications
{
    public static function getType(): string
    {
        return 'SubscriptionCancelledSystemNotification';
    }

    public static function shouldBeRaised(): bool
    {
        if (AngieApplication::accountSettings()->getAccountStatus()->isCancelled()) {
            self::clearNotifications(); // remove notifications of this type and show the new ones

            return true;
        }

        return false;
    }

    public static function accountStatusExpiresInDays(): int
    {
        //@ToDO promeniti expires_at na expires_on
        $expires_at = AngieApplication::accountSettings()->getAccountStatus()->getStatusExpiresAt();

        if (empty($expires_at)) {
            $expires_at = new DateValue();
        }

        return DateValue::now()->daysBetween($expires_at);
    }
}
