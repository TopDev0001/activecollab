<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEventInterface;
use Angie\Events;
use Angie\Inflector;

final class DataObjectPool
{
    const OBJECT_CREATED = 'created';
    const OBJECT_UPDATED = 'updated';
    const OBJECT_ARCHIVED = 'archived';
    const OBJECT_UNARCHIVED = 'unarchived';
    const OBJECT_DELETED = 'deleted';

    /**
     * Cache all objects in this variable, indexed by type and ID.
     *
     * @var array
     */
    public static $pool = [];

    /**
     * Registered type loaders.
     *
     * @var callable[]
     */
    public static $type_loaders = [];

    public static function isCached(string $type, int $id): bool
    {
        $model_type = self::getModelNameByType($type);

        return $model_type && !empty(self::$pool[$model_type][$id]);
    }

    /**
     * Return object by type -> id pair, by forcefully reload it.
     *
     * @param  string           $type
     * @param  int              $id
     * @return DataObject|mixed
     */
    public static function &reload($type, $id, callable $alternative = null)
    {
        return self::get($type, $id, $alternative, true);
    }

    /**
     * Return object by type -> id pair.
     *
     * @param  string          $type
     * @param  int             $id
     * @param  bool            $force_reload
     * @return DataObject|null
     */
    public static function &get($type, $id, callable $alternative = null, $force_reload = false)
    {
        if ($id) {
            $model_type = self::getModelNameByType($type);

            if (empty($model_type)) {
                throw new RuntimeException(sprintf('%s is not data object class.', $type));
            }

            if (empty($force_reload) && !empty(self::$pool[$model_type][$id])) {
                return self::$pool[$model_type][$id];
            } else {
                $object = self::loadById($model_type, $id);

                if ($object instanceof DataObject && $object->isLoaded()) {
                    self::$pool[$model_type][$id] = $object;
                } else {
                    self::$pool[$model_type][$id] = null;
                }

                return self::$pool[$model_type][$id];
            }
        }

        if (is_callable($alternative)) {
            $result = call_user_func($alternative);
        } else {
            $result = $alternative;
        }

        return $result;
    }

    /**
     * Load first object by ID.
     *
     * @param  string     $type
     * @param  int        $id
     * @return DataObject
     */
    private static function loadById($type, $id)
    {
        $type_loader = self::getTypeLoader($type);

        if ($type_loader) {
            $loader_result = $type_loader([$id]);

            if ($loader_result) {
                foreach ($loader_result as $v) {
                    return $v;
                }
            }
        }

        return null;
    }

    /**
     * Return type loader.
     *
     * @param string $type
     */
    public static function getTypeLoader($type): ?callable
    {
        return isset(self::$type_loaders[$type]) && self::$type_loaders[$type]
            ? self::$type_loaders[$type]
            : null;
    }

    /**
     * Add object to the pool.
     */
    public static function introduce(DataObject &$object)
    {
        $model_type = self::getModelNameByType(get_class($object));

        if ($model_type) {
            self::$pool[$model_type][$object->getId()] = $object;
        }
    }

    /**
     * Announce that $object changed its state to $new_lifecycle_state.
     *
     * @param  DataObject|DataObjectLifeCycleEventInterface $object
     * @param  string                                       $new_lifecycle_state
     * @return DataObject
     */
    public static function &announce(
        $object,
        $new_lifecycle_state = self::OBJECT_CREATED,
        array $attributes = null
    )
    {
        if ($object instanceof DataObject) {
            if ($object->isLoaded()) {
                Events::trigger(
                    self::getAnnouncementEventName($object, $new_lifecycle_state),
                    [
                        &$object,
                        $attributes,
                    ]
                );
            }

            return $object;
        } elseif ($object instanceof DataObjectLifeCycleEventInterface) {
            AngieApplication::eventsDispatcher()->trigger($object);

            $result = $object->getObject();

            return $result;
        } else {
            throw new LogicException('Only data object and data object lifecycle events are accepted.');
        }
    }

    private static function getAnnouncementEventName(DataObject $object, $new_lifecycle_state): string
    {
        return 'on_' . Inflector::underscore(get_class($object)) . '_' . $new_lifecycle_state;
    }

    /**
     * Remove object from the pool. If $id_or_ids is NULL, all entries for the given type will be forgotten.
     *
     * @param string    $type
     * @param int|int[] $id_or_ids
     */
    public static function forget($type, $id_or_ids = null)
    {
        $model_type = self::getModelNameByType($type);

        if (empty(self::$pool[$model_type])) {
            return;
        }

        if ($id_or_ids) {
            foreach ((array) $id_or_ids as $id) {
                if (!empty(self::$pool[$model_type][$id])) {
                    unset(self::$pool[$model_type][$id]);
                }
            }
        } elseif ($id_or_ids === null) {
            unset(self::$pool[$model_type]);
        }
    }

    /**
     * Return objects by type -> ids map.
     *
     * @param  array $map
     * @return array
     */
    public static function getByTypeIdsMap($map)
    {
        if ($map && is_foreachable($map)) {
            $result = [];

            foreach ($map as $type => $ids) {
                $result[$type] = self::getByIds($type, $ids);

                if (empty($result[$type])) {
                    unset($result[$type]);
                }
            }

            return $result;
        }

        return null;
    }

    /**
     * Return instances by $type and $ids.
     *
     * @param  string            $type
     * @param  int[]             $ids
     * @return DataObject[]|null
     */
    public static function getByIds($type, $ids)
    {
        if (empty($ids)) {
            return null;
        }

        $type_loader = self::getTypeLoader($type);

        if ($type_loader) {
            /** @var DataObject[] $loader_result */
            $loader_result = $type_loader(array_unique($ids));

            if ($loader_result) {
                $model_type = self::getModelNameByType($type);

                if (empty($model_type)) {
                    throw new RuntimeException($type . ' is not data object class.');
                }

                $objects = [];

                foreach ($loader_result as $v) {
                    $objects[$v->getId()] = $v;

                    if (empty(self::$pool[$model_type])) {
                        self::$pool[$model_type] = [];
                    }

                    self::$pool[$model_type][$v->getId()] = $v;
                }
            } else {
                $objects = null;
            }
        } else {
            $objects = [];

            foreach ($ids as $id) {
                $object = self::get($type, $id);

                if ($object) {
                    $objects[$id] = $object;
                }
            }
        }

        return empty($objects) ? null : $objects;
    }

    /**
     * Register type loader.
     *
     * $type can be a signle type or an array of types
     *
     * $callback can be a closure or callback array
     *
     * @param string[]|string $type
     * @param mixed           $callback
     */
    public static function registerTypeLoader($type, callable $callback)
    {
        if (is_iterable($type)) {
            foreach ($type as $v) {
                self::$type_loaders[$v] = $callback;
            }
        } else {
            self::$type_loaders[$type] = $callback;
        }
    }

    public static function clear()
    {
        self::$pool = [];
    }

    private static $cached_types = [];

    public static function getModelNameByType(string $type): ?string
    {
        if (empty(self::$cached_types[$type]) && class_exists($type)) {
            $type_reflection = new ReflectionClass($type);

            if ($type_reflection->isSubclassOf(DataObject::class)) {
                self::$cached_types[$type] = $type_reflection->getConstant('MODEL_NAME');
            }
        }

        return empty(self::$cached_types[$type]) ? null : self::$cached_types[$type];
    }
}
