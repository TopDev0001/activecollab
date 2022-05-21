<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\RecurringProfilesToTriggerResolver;

use RecurringProfile;
use RecurringProfiles;

class RecurringProfilesToTriggerResolver implements RecurringProfilesToTriggerResolverInterface
{
    /**
     * Return profiles that need to be sent on a given date.
     *
     * @return RecurringProfile[]|iterable
     */
    public function getProfilesToTrigger(): iterable
    {
        $profiles_to_trigger = RecurringProfiles::find(
            [
                'conditions' => [
                    '`is_enabled` = ? AND (`occurrences` = ? OR `occurrences` > `triggered_number`)',
                    true,
                    0,
                ],
            ]
        );

        if (empty($profiles_to_trigger)) {
            $profiles_to_trigger = [];
        }

        return $profiles_to_trigger;
    }
}
