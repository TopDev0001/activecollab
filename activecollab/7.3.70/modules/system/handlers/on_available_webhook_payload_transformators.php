<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * on_available_webhook_payload_transformators event handler.
 *
 * @package ActiveCollab.modules.system
 * @subpackage handlers
 */

use ActiveCollab\Module\System\Utils\Webhooks\Transformator\SlackWebhookPayloadTransformator;
use ActiveCollab\Module\System\Utils\Webhooks\Transformator\ZapierWebhookPayloadTransformator;

/**
 * Handle on_available_webhook_payload_transformators event.
 */
function system_handle_on_available_webhook_payload_transformators(array &$transformators)
{
    $transformators[] = SlackWebhookPayloadTransformator::class;
    $transformators[] = ZapierWebhookPayloadTransformator::class;
}
