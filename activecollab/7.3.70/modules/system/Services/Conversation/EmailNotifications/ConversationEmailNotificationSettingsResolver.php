<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation\EmailNotifications;

use DB;

class ConversationEmailNotificationSettingsResolver implements ConversationEmailNotificationSettingsResolverInterface
{
    public function getUserIdsWithDisabledEmailNotifications(): array
    {
        $results = DB::execute(
            'SELECT value, parent_id FROM config_option_values WHERE parent_type = ? AND name = ?',
            'User',
            'chat_email_notifications'
        );

        $results = $results ? $results->toArray() : [];

        $ids = [];
        foreach ($results as $result) {
            if (!unserialize($result['value'])) {
                $ids[] = (int) $result['parent_id'];
            }
        }

        return $ids;
    }
}
