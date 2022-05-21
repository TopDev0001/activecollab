<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie;

use Angie\Trash\Sections;
use DataManager;
use DateValue;
use DB;
use User;

/**
 * An interface for work with trashed objects.
 *
 * @package Angie
 */
final class Trash
{
    const DELETE_PER_ITERATION = 1000;

    /**
     * Return a list of trashed objects.
     *
     * @return Sections
     */
    public static function getObjects(User $user)
    {
        $sections = new Sections();

        Events::trigger('on_trash_sections', [&$sections, &$user]);

        return $sections;
    }

    /**
     * @param  int|null       $delete_per_iteration
     * @param  DateValue|null $trashed_before
     * @return array
     */
    public static function emptyTrash(User $user, $delete_per_iteration = null, $trashed_before = null)
    {
        $deleted = [];
        $trash_sections = self::getObjects($user);

        if ($trash_sections->count()) {
            $left_to_delete = self::getDeletePerIteration($delete_per_iteration);

            foreach ($trash_sections->getEmptyInWaves() as $types) {
                foreach ($types as $type) {
                    if ($left_to_delete < 1) {
                        break;
                    }

                    if (isset($trash_sections[$type]) && count($trash_sections[$type])) {
                        foreach (self::queryObjectsToDelete($trash_sections, $type, $left_to_delete, $trashed_before) as $object) {
                            $object_id = $object->getId();

                            $object->delete();

                            if (empty($deleted[$type])) {
                                $deleted[$type] = [];
                            }

                            $deleted[$type][] = $object_id;
                            --$left_to_delete;
                        }
                    }
                }
            }
        }

        return $deleted;
    }

    /**
     * Return how many items should be deleted per iteration based on input.
     *
     * @param  int|null $delete_per_iteration
     * @return int
     */
    private static function getDeletePerIteration($delete_per_iteration)
    {
        $delete_per_iteration = (int) $delete_per_iteration;

        if ($delete_per_iteration < 1 || $delete_per_iteration > self::DELETE_PER_ITERATION) {
            return self::DELETE_PER_ITERATION;
        } else {
            return $delete_per_iteration;
        }
    }

    /**
     * @param  string         $type
     * @param  int            $left_to_delete
     * @param  DateValue|null $trashed_before
     * @return \DataObject[]
     */
    private static function queryObjectsToDelete(Sections &$trash_sections, $type, $left_to_delete, $trashed_before = null)
    {
        $manager_class = Inflector::pluralize($type);

        if (DataManager::isManagerClass($manager_class)) {
            if ($trashed_before instanceof DateValue) {
                $conditions = DB::prepare('id IN (?) AND trashed_on < ?', array_keys($trash_sections[$type]), $trashed_before);
            } else {
                $conditions = DB::prepare('id IN (?)', array_keys($trash_sections[$type]));
            }

            $result = call_user_func("$manager_class::find", ['conditions' => $conditions, 'limit' => $left_to_delete]);
        }

        if (empty($result)) {
            $result = [];
        }

        return $result;
    }
}
