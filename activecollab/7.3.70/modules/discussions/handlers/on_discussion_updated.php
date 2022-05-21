<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * on_discussion_updated event handler.
 *
 * @package ActiveCollab.modules.discussions
 * @subpackage handlers
 */

use ActiveCollab\Module\System\Utils\NotificationRecipientsCleaner\NotificationRecipientsCleanerInterface;

function discussions_handle_on_discussion_updated(Discussion $discussion, array $attributes)
{
    if (array_key_exists('moved_to_project', $attributes)) {
        AngieApplication::getContainer()->get(NotificationRecipientsCleanerInterface::class)->clean($discussion);
    }
}
