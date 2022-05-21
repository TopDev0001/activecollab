<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Files\Events\DataObjectLifeCycleEvents\FileEvents;

use ActiveCollab\Foundation\Events\WebhookEvent\WebhookEventInterface;

interface FileCreatedEventInterface extends FileLifecycleEventInterface, WebhookEventInterface
{
}