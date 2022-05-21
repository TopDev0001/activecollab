<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Handle public unsubscribe event.
 *
 * @package ActiveCollab.modules.system
 * @subpackage helpers
 */

/**
 * Handle on_handle_public_unsubscribe event.
 *
 * @param string    $notification
 * @param array     $parts
 * @param bool|null $unsubscribed
 * @param string    $message
 * @param string    $undo_code
 */
function system_handle_on_handle_public_unsubscribe($notification, $parts, &$unsubscribed, &$message, &$undo_code)
{
    if ($notification == 'MRNGPPR' && $unsubscribed === null) {
        [$user_id, $subscription_code] = $parts;

        if ($user_id && $subscription_code) {
            $user = Users::findById($user_id);

            if ($user instanceof User && $user->isActive()) {
                if (strtoupper($user->getAdditionalProperty('subscription_code')) == $subscription_code) {
                    ConfigOptions::setValueFor('notifications_user_send_morning_paper', $user, false);

                    $unsubscribed = true;
                    $message = lang(':user_name has been successfully removed from Morning Paper list', [
                        'user_name' => $user->getDisplayName(),
                    ]);

                    $undo_code = MorningPaper::getSubscriptionCode($user);
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
