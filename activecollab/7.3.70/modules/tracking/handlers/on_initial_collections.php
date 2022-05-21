<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_initial_collections event handler.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage handlers
 */

/**
 * @param array $collections
 * @param User  $user
 */
function tracking_handle_on_initial_collections(array &$collections, User $user)
{
    $collections['expense_categories'] = ExpenseCategories::prepareCollection(DataManager::ALL, $user);
}
