<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\NotificationRecipientsCleaner;

use DataObject;

interface NotificationRecipientsCleanerInterface
{
    public function clean(DataObject $projectElement): void;
}
