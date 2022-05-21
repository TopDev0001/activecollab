<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Webhooks\Transformator;

use WebhookPayloadTransformatorInterface;

interface SocketPayloadTransformatorInterface extends WebhookPayloadTransformatorInterface
{
    /**
     * Event data payload limit for pusher is 10kb (https://pusher.com/docs/rest_api#method-post-event).
     */
    const PUSHER_PAYLOAD_LIMIT = 9216;
}
