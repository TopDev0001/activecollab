<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Model\Categories\ICategoriesContext;

class Categories extends BaseCategories
{
    /**
     * Return new collection.
     *
     * Valid collections:
     *
     * - project_categories
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if ($collection_name === 'project_categories') {
            $collection->setConditions('type = ?', ProjectCategory::class);
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    public static function canManage(User $user, string $type, ICategoriesContext $parent = null): bool
    {
        if ($user->isPowerUser()) {
            return true;
        }

        return $parent instanceof Project && $parent->isLeader($user);
    }

    /**
     * Return category ID - name map based on input parameters.
     *
     * Result can be filtered by parent or type, both or none (all categories)
     *
     * @param ICategoriesContext|ApplicationObject|null $parent
     */
    public static function getIdNameMap(
        ICategoriesContext $parent = null,
        string $type = null
    ): ?array
    {
        $cache_key = null;

        $conditions = [];
        if ($parent && !$parent->isNew()) {
            $conditions[] = DB::prepare(
                '(`parent_type` = ? AND `parent_id` = ?)',
                get_class($parent),
                $parent->getId()
            );
        }

        if ($type) {
            $conditions[] = DB::prepare('(type IN (?))', $type);
        }

        if (is_string($type)) {
            $cache_key = 'categories_' . strtolower($type);

            if ($parent && !$parent->isNew()) {
                $cached_values = AngieApplication::cache()->getByObject($parent, $cache_key);
            } else {
                $cached_values = AngieApplication::cache()->get($cache_key);
            }

            if ($cached_values) {
                return $cached_values;
            }
        }

        if (count($conditions)) {
            $rows = DB::execute('SELECT id, name FROM categories WHERE ' . implode(' AND ', $conditions) . ' ORDER BY name');
        } else {
            $rows = DB::execute('SELECT id, name FROM categories ORDER BY name');
        }

        if ($rows) {
            $result = [];

            foreach ($rows as $row) {
                $result[(int) $row['id']] = $row['name'];
            }

            if (!is_null($cache_key)) {
                if ($parent) {
                    AngieApplication::cache()->setByObject($parent, $cache_key, $result);
                } else {
                    AngieApplication::cache()->set($cache_key, $result);
                }
            }

            return $result;
        }

        return null;
    }

    /**
     * Return category ID-s by list of category names.
     *
     * @param  array                                     $names
     * @param  string                                    $type
     * @param  ICategoriesContext|ApplicationObject|null $parent
     * @return array
     */
    public static function getIdsByNames($names, $type, ICategoriesContext $parent = null)
    {
        if ($names) {
            if ($parent instanceof ICategoriesContext) {
                $ids = DB::executeFirstColumn('SELECT DISTINCT id FROM categories WHERE parent_type = ? AND parent_id = ? AND name IN (?) AND type = ?', get_class($parent), $parent->getId(), $names, $type);
            } else {
                $ids = DB::executeFirstColumn('SELECT DISTINCT id FROM categories WHERE name IN (?) AND type = ?', $names, $type);
            }

            if ($ids) {
                foreach ($ids as $k => $v) {
                    $ids[$k] = (int) $v;
                }
            }

            return $ids;
        }

        return null;
    }

    public static function getCategoryName(
        int $category_id,
        string $default = null
    ): ?string
    {
        $name = DB::executeFirstCell('SELECT `name` FROM `categories` WHERE `id` = ?', $category_id);

        if (empty($name)) {
            $name = $default;
        }

        return $name;
    }

    public static function getNamesByIds(array $ids): array
    {
        $id_name_map = Categories::getIdNameMap();

        $result = [];

        foreach ($ids as $id) {
            if (isset($id_name_map[$id])) {
                $result[$id] = $id_name_map[$id];
            } else {
                $result[$id] = '--';
            }
        }

        return $result;
    }
}
