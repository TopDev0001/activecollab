<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Framework level favorites manager.
 *
 * @package angie.framework.favorites
 * @subpackage models
 */
abstract class FwFavorites
{
    /**
     * Returns true if $parent is marked as favorite by $user.
     *
     * $parent can be IFavorite instance or an array where first element is
     * class name and second parameter is parent ID
     *
     * @param  mixed             $parent
     * @return bool
     * @throws InvalidParamError
     */
    public static function isFavorite($parent, User $user)
    {
        if ($parent instanceof IFavorite) {
            $parent_type = get_class($parent);
            $parent_id = $parent->getId();
        } elseif (is_array($parent) && count($parent) == 2) {
            [$parent_type, $parent_id] = $parent;
        } else {
            throw new InvalidParamError('$parent', $parent, '$parent should be an instance of IFavorite class or an array');
        }

        return in_array("$parent_type-$parent_id", self::getUserCache($user));
    }

    /**
     * Rebuild favorites cache for given user.
     *
     * @param  bool  $refresh
     * @return array
     */
    protected static function getUserCache(User $user, $refresh = false)
    {
        return AngieApplication::cache()->getByObject($user, 'favorites', function () use ($user) {
            $result = [];

            $rows = DB::execute('SELECT parent_type, parent_id FROM favorites WHERE user_id = ?', $user->getId());
            if ($rows) {
                foreach ($rows as $row) {
                    $result[] = "$row[parent_type]-$row[parent_id]";
                }
            }

            return $result;
        }, $refresh);
    }

    /**
     * Add parent to favorites for $user.
     *
     * @param  ApplicationObject|IFavorite $parent
     * @throws Exception
     */
    public static function addToFavorites(IFavorite $parent, User $user)
    {
        try {
            DB::beginWork();

            DB::execute('REPLACE INTO favorites (parent_type, parent_id, user_id) VALUES (?, ?, ?)', get_class($parent), $parent->getId(), $user->getId());
            $parent->touch();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }

        AngieApplication::cache()->removeByObject($parent);
        AngieApplication::cache()->removeByObject($user, 'favorites');
    }

    /**
     * Remove $parent from user's favorites.
     *
     * @throws Exception
     */
    public static function removeFromFavorites(IFavorite $parent, User $user)
    {
        try {
            DB::beginWork();

            DB::execute('DELETE FROM favorites WHERE parent_type = ? AND parent_id = ? AND user_id = ?', get_class($parent), $parent->getId(), $user->getId());
            $parent->touch();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }

        AngieApplication::cache()->removeByObject($parent);
        AngieApplication::cache()->removeByObject($user, 'favorites');
    }

    // ---------------------------------------------------
    //  Finders
    // ---------------------------------------------------
    /**
     * Return ID-s by user and type.
     *
     * @param  mixed $type
     * @return array
     */
    public static function findIdsByUserAndType(IUser $user, $type)
    {
        return DB::executeFirstColumn('SELECT parent_id FROM favorites WHERE user_id = ? AND parent_type = ?', $user->getId(), $type);
    }

    /**
     * Return object ID-s by parent types.
     *
     * @param  array $types
     * @return array
     */
    public static function findIdsByUserAndTypes(IUser $user, $types)
    {
        $rows = DB::execute('SELECT parent_type, parent_id FROM favorites WHERE user_id = ? AND parent_type IN (?)', $user->getId(), $types);

        if ($rows) {
            $result = [];

            foreach ($rows as $row) {
                if (isset($result[$row['parent_type']])) {
                    $result[$row['parent_type']][] = (int) $row['parent_id'];
                } else {
                    $result[$row['parent_type']] = [(int) $row['parent_id']];
                }
            }

            return $result;
        } else {
            return null;
        }
    }

    /**
     * Find by user.
     *
     * @return ApplicationObject[]
     */
    public static function findFavoriteObjectsByUser(User $user)
    {
        $favorites = DB::execute('SELECT parent_id, parent_type FROM favorites WHERE user_id = ?', $user->getId());

        if ($favorites) {
            $result = [];
            foreach ($favorites as $favorite) {
                try {
                    $object_id = $favorite['parent_id'];
                    $object_type = $favorite['parent_type'];

                    if (!($object_id && $object_type)) {
                        throw new Error('Favorite requires parent id and parent type');
                    }

                    $object = DataObjectPool::get($object_type, $object_id);

                    if ($object instanceof ApplicationObject) {
                        if (method_exists($object, 'canView')) {
                            if ($object->canView($user)) {
                                if ($object instanceof ITrash) {
                                    if (!$object->getIsTrashed()) { // avoid deleted objects
                                        $result[] = $object;
                                    }
                                } else {
                                    $result[] = $object;
                                }
                            }
                        } else {
                            $result[] = $object;
                        }
                    } else {
                        DB::execute('DELETE FROM favorites WHERE parent_type = ? AND parent_id = ?', $object_type, $object_id);
                    }
                } catch (Exception $e) {
                    continue; // skip item
                }
            }

            usort($result, function (ApplicationObject $a, ApplicationObject $b) {
                return strcmp(strtolower($a->getName()), strtolower($b->getName()));
            });

            return $result;
        }

        return null;
    }

    /**
     * Find favorite objects list.
     *
     * @return DbResult
     */
    public static function findFavoriteObjectsList(User $user)
    {
        return DB::execute('SELECT parent_id, parent_type FROM favorites WHERE user_id = ?', $user->getId());
    }

    // ---------------------------------------------------
    //  Mass-management
    // ---------------------------------------------------
    /**
     * Drop all records by user.
     */
    public static function deleteByUser(User $user)
    {
        DB::execute('DELETE FROM favorites WHERE user_id = ?', $user->getId());
    }

    /**
     * Drop records by object.
     */
    public static function deleteByParent(IFavorite $parent)
    {
        DB::execute('DELETE FROM favorites WHERE parent_type = ? AND parent_id = ?', get_class($parent), $parent->getId());
    }
}
