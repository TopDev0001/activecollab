<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\UsersDisplayNameResolver;

use IMembers;
use IUser;

class UsersDisplayNameResolver implements UsersDisplayNameResolverInterface
{
    public function getNameFor(IMembers $context, ?IUser $for = null, ?string $default = null): ?string
    {
        $members = $context->getMembers();

        if (!$members) {
            return $default;
        }

        if ($for) {
            $members = array_values(
                array_filter($members->toArray(), function ($member) use ($for) {
                    return !$member->is($for);
                })
            );

            if (empty($members)) {
                return $default;
            }
        }

        $count = count($members);

        if ($count === 1) {
            return $members[0]->getFirstName();
        } elseif ($count === 2) {
            return sprintf(
                '%s and %s',
                $members[0]->getFirstName(),
                $members[1]->getFirstName(),
            );
        } elseif ($count === 3) {
            return sprintf(
                '%s, %s and %s',
                $members[0]->getFirstName(),
                $members[1]->getFirstName(),
                $members[2]->getFirstName(),
            );
        } else {
            return sprintf(
                '%s, %s and %s others',
                $members[0]->getFirstName(),
                $members[1]->getFirstName(),
                $count - 2,
            );
        }
    }
}
