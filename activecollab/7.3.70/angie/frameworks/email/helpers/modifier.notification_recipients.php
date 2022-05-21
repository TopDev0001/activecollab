<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * List notification recipients.
 *
 * @package angie.frameworks.email
 * @subpackage helpers
 */

/**
 * Return notification context prefix.
 *
 * @param  Notification|IUser[] $from
 * @param  IUser|null           $skip
 * @param  string               $link_style
 * @param  Language             $language
 * @return string
 */
function smarty_modifier_notification_recipients($from, $skip = null, $link_style = '', Language $language = null)
{
    $recipients = [];
    $recipient_links = [];

    if ($from instanceof Notification) {
        $recipients = $from->getRecipients();
    } elseif (is_array($from)) {
        $recipients = $from;
    }

    if ($recipients && count($recipients)) {
        $skip_email = $skip instanceof IUser ? strtolower($skip->getEmail()) : null;

        foreach ($recipients as $k => $recipient) {
            if ($recipient instanceof IUser) {
                $recipient_email = strtolower($recipient->getEmail());
                $recipient_name = $recipient->getName();

                if (empty($recipient_name)) {
                    $recipient_name = $recipient_email;
                }
            } else {
                if (is_valid_email($k) && is_string($recipient)) {
                    $recipient_email = strtolower($k);
                    $recipient_name = $recipient;
                } else {
                    continue;
                }
            }

            if ($skip_email && $skip_email == $recipient_email) {
                continue;
            }

            $recipient_links[] = '<a href="mailto:' . clean($recipient_email) . '" style="' . $link_style . '">' . clean($recipient_name) . '</a>';
        }

        switch (count($recipient_links)) {
            case 0:
                return '';
            case 1:
                return $recipient_links[0];
            default:
                $last = array_pop($recipient_links);

                return lang(':comma_separated_list and :last', [
                    'comma_separated_list' => implode(', ', $recipient_links),
                    'last' => $last,
                ], false, $language);
        }
    }

    return '';
}
