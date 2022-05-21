<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_notification_inspector event handler implementation.
 *
 * @package angie.frameworks.reminders
 * @subpackage handlers
 */

/**
 * Handle on_notification_inspector event.
 *
 * @param IProjectElement $context
 * @param mixed           $subcontext
 * @param IUser           $recipient
 * @param Language        $language
 * @param Angie\NamedList $properties
 * @param string          $link_style
 */
function reminders_handle_on_notification_inspector(&$context, &$subcontext, &$recipient, &$language, &$properties, $link_style)
{
    if ($subcontext instanceof Reminder) {
        $properties->add('reminder_by', [
            'label' => lang('Reminder set by', null, null, $language),
            'value' => clean($subcontext->getCreatedBy()->getDisplayName()),
        ]);
    }
}
