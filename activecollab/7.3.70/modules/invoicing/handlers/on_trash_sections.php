<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_trash_sections event handler.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage handlers
 */

/**
 * Handle on_trash_sections event.
 *
 * @param \Angie\Trash\Sections $sections
 */
function invoicing_handle_on_trash_sections(Angie\Trash\Sections &$sections, User $user)
{
    if ($user->isOwner()) {
        $invoices_id_name_map = DB::executeIdNameMap('SELECT id, number as name FROM invoices WHERE is_trashed = ? ORDER BY trashed_on DESC', true);
        $estimates_id_name_map = DB::executeIdNameMap('SELECT id, name FROM estimates WHERE is_trashed = ? ORDER BY trashed_on DESC', true);
    } elseif ($user->isMember()) {
        $invoices_id_name_map = DB::executeIdNameMap('SELECT id, number as name FROM invoices WHERE trashed_by_id = ? AND is_trashed = ? ORDER BY trashed_on DESC', $user->getId(), true);
        $estimates_id_name_map = DB::executeIdNameMap('SELECT id, name FROM estimates WHERE trashed_by_id = ? AND is_trashed = ? ORDER BY trashed_on DESC', $user->getId(), true);
    } else {
        $invoices_id_name_map = null;
        $estimates_id_name_map = null;
    }

    $sections->registerTrashedObjects('Invoice', $invoices_id_name_map);
    $sections->registerTrashedObjects('Estimate', $estimates_id_name_map);
}
