<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_visible_object_paths event handler.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage handlers
 */

/**
 * @param ApplicationObject|null $in
 */
function invoicing_handle_on_visible_object_paths(User $user, array &$contexts, array &$ignore_contexts, &$in)
{
    if (empty($in) && ($user->isOwner() || $user->isFinancialManager())) {
        $contexts['invoices/*'] = true;
        $contexts['estimates/*'] = true;
    }
}
