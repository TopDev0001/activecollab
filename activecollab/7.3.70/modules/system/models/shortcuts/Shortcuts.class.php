<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Shortcuts class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class Shortcuts extends BaseShortcuts
{
    public static function prepareRelativeCursorCollection(
        string $collection_name,
        User $user
    ): RelativeCursorModelCollection
    {
        if (str_starts_with($collection_name, 'user_shortcuts')) {
            return self::prepareRelativeCursorShortcutsCollection($collection_name, $user);
        } else {
            throw new RuntimeException("Collection name '$collection_name' does not exist.");
        }
    }

    public static function getNextPositionForUser(User $user): int
    {
        return DB::executeFirstCell(
                'SELECT MAX(`position`) FROM `shortcuts` WHERE `created_by_id` = ?',
                $user->getId()
            ) + 1;
    }

    private static function prepareRelativeCursorShortcutsCollection(
        string $collection_name,
        User $user
    ): RelativeCursorModelCollection
    {
        $collection = parent::prepareRelativeCursorCollection($collection_name, $user);

        $collection->setCursorField('position');

        if (isset($_GET['cursor'], $_GET['last_id'])) {
            $collection->setCursor((int) $_GET['cursor']);
            $collection->setLastId((int) $_GET['last_id']);
        }

        $collection->setConditions(
            'created_by_id = ?',
            $user->getId()
        );

        if (isset($_GET['limit'])) {
            $collection->setLimit((int) $_GET['limit']);
        }

        return $collection;
    }

    public static function batchScrapBy(array $shortcut_ids, User $by)
    {
        $conditions = DB::prepare(
            'id IN (?) AND created_by_id = ?',
            $shortcut_ids,
            $by->getId()
        );
        $shortcuts = self::find(['conditions' => $conditions]);

        if (!empty($shortcuts)) {
            self::batchScrap($shortcuts);
        }

        return true;
    }
}
