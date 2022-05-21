<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Framework level subscription manager implementation.
 *
 * @package angie.frameworks.subscriptions
 * @subpackage models
 */
abstract class FwSubscriptions extends BaseSubscriptions
{
    /**
     * Delete subscriptions by parent.
     *
     * @param ApplicationObject|ISubscriptions $parent
     */
    public static function deleteByParent(ISubscriptions $parent)
    {
        DB::execute('DELETE FROM subscriptions WHERE parent_type = ? AND parent_id = ?', get_class($parent), $parent->getId());

        AngieApplication::cache()->removeByObject($parent);
    }

    /**
     * Delete subscriptions by user.
     */
    public static function deleteByUser(User $user)
    {
        $ids = [];

        if ($user instanceof User) {
            $ids = DB::executeFirstColumn('SELECT id FROM subscriptions WHERE user_id = ?', $user->getId());
        } elseif ($user instanceof AnonymousUser) {
            $ids = DB::executeFirstColumn(
                'SELECT id FROM subscriptions WHERE user_id = ? AND user_email = ?',
                0,
                $user->getEmail()
            );
        }

        if ($subscriptions = self::findByIds($ids)) {
            foreach ($subscriptions as $subscription) {
                $subscription->delete();
            }
        }
    }

    /**
     * Delete subscription record based on id and code.
     *
     * @param int    $id
     * @param string $code
     */
    public static function deleteByIdAndCode($id, $code)
    {
        DB::execute('DELETE FROM subscriptions WHERE id = ? AND code = ?', $id, $code);
    }

    /**
     * Delete entries by parents.
     *
     * $parents is an array where key is parent type and value is array of
     * object ID-s of that particular parent
     *
     * @param array     $parents
     * @param User|null $user
     */
    public static function deleteByParents($parents, $user = null)
    {
        if ($parent_conditions = Subscriptions::typeIdsMapToConditions($parents)) {
            if ($user instanceof User) {
                DB::execute('DELETE FROM subscriptions WHERE user_id = ? AND ' . $parent_conditions, $user->getId());
            } else {
                DB::execute('DELETE FROM subscriptions WHERE ' . $parent_conditions);
            }

            foreach ($parents as $object_class => $object_ids) {
                $manager_class = Angie\Inflector::pluralize($object_class);

                if (class_exists($manager_class, true) && method_exists($manager_class, 'clearCacheFor')) {
                    call_user_func([$manager_class, 'clearCacheFor'], $object_ids);
                }
            }
        }
    }
}
