<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_notification_context_view_url event handler.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage handlers
 */

/**
 * Handle context view URL event.
 *
 * @param IUser   $user
 * @param Invoice $context
 * @param string  $context_view_url
 */
function invoicing_handle_on_notification_context_view_url(&$user, &$context, &$context_view_url)
{
    if ($context instanceof Invoice) {
        $context_view_url = $context->getPublicUrl();
    }
}
