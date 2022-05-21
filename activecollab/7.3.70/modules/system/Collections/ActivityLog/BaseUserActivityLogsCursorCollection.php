<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Collections\ActivityLog;

use ActiveCollab\Module\System\Collections\ActivityLog\Traits\IForOrBy;
use User;
use Users;

abstract class BaseUserActivityLogsCursorCollection extends BaseActivityLogsCursorCollection
{
    use IForOrBy;

    public function getModelName(): string
    {
        return Users::class;
    }

    public function getTimestampHash(): string
    {
        return sha1(
            sprintf(
                '%s,%s',
                $this->getForOrBy()->getUpdatedOn()->toMySQL(),
                $this->getActivityLogsCollection()->getTimestampHash('updated_on')
            )
        );
    }

    protected function checkCollectionNameValues(): bool
    {
        return strpos($this->dates, ':') !== false
            && $this->getForOrBy() instanceof User
            && $this->getWhosAsking() instanceof User;
    }
}
