<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Handle public unsubscribe event.
 *
 * @package angie.frameworks.subscriptions
 * @subpackage helpers
 */

/**
 * Handle on_handle_public_unsubscribe event.
 *
 * @param string    $notification
 * @param array     $parts
 * @param bool|null $unsubscribed
 * @param string    $message
 */
function subscriptions_handle_on_handle_public_unsubscribe($notification, $parts, &$unsubscribed, &$message)
{
    if ($notification == 'SUBS' && $unsubscribed === null) {
        [$subscription_id, $subscription_code] = $parts;

        if ($subscription_id && $subscription_code) {
            $subscription = Subscriptions::findById($subscription_id);

            if ($subscription instanceof Subscription) {
                if (strtoupper($subscription->getCode()) == $subscription_code) {
                    $parent = $subscription->getParent();
                    $user = $subscription->getUser();

                    $subscription->delete();

                    if ($user instanceof User) {
                        AngieApplication::cache()->removeByObject($user, 'subscriptions');
                    }

                    $unsubscribed = true;
                    $message = lang('User :email_address has been successfully removed from ":object_name" notification list', [
                        'email_address' => $subscription->getUserEmail(),
                        'object_name' => $parent ? $parent->getName() : $subscription->getParentType() . ' #' . $subscription->getParentId(),
                    ]);
                } else {
                    $this->response->notFound();
                }
            } else {
                $unsubscribed = false;
            }
        } else {
            $unsubscribed = false;
        }
    }
}
