<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Config option manager.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
final class ConfigOptions
{
    /**
     * @var array
     */
    private static $resets_initial_settings_timestamp = false;

    /**
     * @var bool|array
     */
    private static $clear_cache = false;

    /**
     * Cached array of exists value.
     *
     * @var array
     */
    private static $exists_cache = [];
    /**
     * List of protected configuration options.
     *
     * @var array
     */
    private static $protected_options = [];
    /**
     * Protected options loaded flag.
     *
     * @var bool
     */
    private static $protected_options_event_triggered = false;

    /**
     * Set value for a given object.
     *
     * This function can be called in following ways:
     *
     * ConfigOptions::setValue('Option Name', 'Value');
     *
     * as well as:
     *
     * ConfigOptions::setValeu(array(
     *   'Option 1' => 'Value 1',
     *   'Option 2' => 'Value 2',
     * ));
     *
     * @param  string|array $name
     * @param  mixed        $value
     * @param  bool         $clear_for_cache
     * @param  bool         $bulk
     * @return mixed
     * @throws Exception
     */
    public static function setValue($name, $value = null, $clear_for_cache = false, $bulk = false)
    {
        if (is_scalar($name)) {
            if (self::exists($name, AngieApplication::isInProduction())) {
                if (self::getValue($name, false) === $value) {
                    return $value;
                }

                DB::execute('UPDATE config_options SET value = ?, updated_on = UTC_TIMESTAMP() WHERE name = ?', serialize($value), $name);

                if (!$bulk) {
                    self::resetInitialSettingsTimestampIfNeeded([$name]);
                    self::clearCacheIfNeeded([$name]);

                    AngieApplication::cache()->remove('config_options');
                }

                if ($clear_for_cache) {
                    AngieApplication::cache()->clearModelCache();
                }

                return $value;
            } else {
                throw new ConfigOptionDnxError($name);
            }
        } else {
            if (is_array($name)) {
                foreach ($name as $k => $v) {
                    self::setValue($k, $v, false, true);
                }

                self::resetInitialSettingsTimestampIfNeeded(array_keys($name));
                self::clearCacheIfNeeded(array_keys($name));
                AngieApplication::cache()->remove('config_options');

                if ($clear_for_cache) {
                    AngieApplication::cache()->clearModelCache();
                }

                return $name;
            } else {
                throw new InvalidParamError('name', $name, 'We expect a config option value, or an array of options and their new values');
            }
        }
    }

    /**
     * Check if specific configuration option exists.
     *
     * @param  string $name
     * @param  bool   $use_cache
     * @return bool
     */
    public static function exists($name, $use_cache = true)
    {
        if (!array_key_exists($name, self::$exists_cache) || !$use_cache) {
            self::$exists_cache[$name] = (bool) DB::executeFirstCell('SELECT COUNT(*) AS "row_count" FROM config_options WHERE name = ?', $name);
        }

        return self::$exists_cache[$name];
    }

    /**
     * Return value by name.
     *
     * If $name is an array, system will get array of configuration option
     * values and return them as associative array
     *
     * Set $use_cache to false if you want this method to ignore cached values
     *
     * @param  mixed                $name
     * @param  bool                 $use_cache
     * @return mixed
     * @throws ConfigOptionDnxError
     * @throws InvalidParamError
     */
    public static function getValue($name, $use_cache = true)
    {
        if (empty($name)) {
            throw new InvalidParamError('name', $name);
        }

        $find = (array) $name;

        $single = $find !== $name; // if we had conversion to array, we had scalar

        $cached_values = AngieApplication::cache()->get('config_options', function () {
            $options = [];

            foreach (DB::execute('SELECT name, value FROM config_options') as $config_option) {
                $options[$config_option['name']] = empty($config_option['value']) ? null : unserialize($config_option['value']);
            }

            return $options;
        });

        $values = [];

        foreach ($find as $option) {
            if ($use_cache) {
                if (array_key_exists($option, $cached_values)) {
                    $values[$option] = $cached_values[$option];
                    continue;
                } else {
                    throw new ConfigOptionDnxError($option);
                }
            } else {
                if ($row = DB::executeFirstRow('SELECT value FROM config_options WHERE name = ?', $option)) {
                    $values[$option] = !is_null($row['value']) ? unserialize($row['value']) : null;
                    if (is_array($cached_values)) {
                        $cached_values[$option] = $values[$option];
                    } else {
                        $cached_values = [$option => $values[$option]];
                    }
                } else {
                    throw new ConfigOptionDnxError($option);
                }
            }
        }

        return $single ? array_shift($values) : $values;
    }

    /**
     * Check updated options and flush cache if needed.
     *
     * @param array $updated_config_options
     */
    private static function clearCacheIfNeeded($updated_config_options)
    {
        foreach ($updated_config_options as $config_option) {
            if (self::shouldClearCache($config_option)) {
                AngieApplication::cache()->clear();
            }
        }
    }

    private static function shouldClearCache($config_option_name)
    {
        if (self::$clear_cache === false) {
            self::$clear_cache = [];
            Angie\Events::trigger(
                'on_clear_cache',
                [
                    &self::$clear_cache,
                ]
            );
        }

        return in_array($config_option_name, self::$clear_cache);
    }

    /**
     * Check updated options and reset initial settings timestamp if needed.
     *
     * @param  array             $updated_config_options
     * @throws InvalidParamError
     */
    private static function resetInitialSettingsTimestampIfNeeded($updated_config_options)
    {
        foreach ($updated_config_options as $updated_config_option) {
            if ($updated_config_option == 'initial_settings_timestamp') {
                continue;
            }

            if (self::updateResetsInitialSettingsTimestamp($updated_config_option)) {
                DB::execute(
                    'UPDATE `config_options` SET `value` = ?, `updated_on` = UTC_TIMESTAMP() WHERE `name` = ?',
                    serialize(AngieApplication::currentTimestamp()->getCurrentTimestamp()),
                    'initial_settings_timestamp'
                );
                AngieApplication::cache()->remove('config_options');

                return;
            }
        }
    }

    /**
     * @param  string $name
     * @return bool
     */
    public static function updateResetsInitialSettingsTimestamp($name)
    {
        if (self::$resets_initial_settings_timestamp === false) {
            self::$resets_initial_settings_timestamp = [];
            Angie\Events::trigger(
                'on_resets_initial_settings_timestamp',
                [
                    &self::$resets_initial_settings_timestamp,
                ]
            );
        }

        return in_array($name, self::$resets_initial_settings_timestamp);
    }

    /**
     * Get value for a given parent object.
     *
     * @param  string|string[]           $name
     * @param  IConfigContext|DataObject $for
     * @param  bool                      $use_cache
     * @return mixed
     */
    public static function getValueFor($name, IConfigContext $for, $use_cache = true)
    {
        $find = (array) $name;

        $cached_values = AngieApplication::cache()->getByObject($for, 'config_options');
        $values = [];

        foreach ($find as $option) {
            if ($use_cache && is_array($cached_values) && array_key_exists($option, $cached_values)) {
                $values[$option] = $cached_values[$option];
                continue;
            }

            if ($row = DB::executeFirstRow('SELECT value FROM config_option_values WHERE name = ? AND parent_type = ? AND parent_id = ?', $option, self::getParentTypeByObject($for), $for->getId())) {
                $values[$option] = $row['value'] ? unserialize($row['value']) : null;
            } else {
                $values[$option] = self::getValue($option, $use_cache);
            }

            if (is_array($cached_values)) {
                $cached_values[$option] = $values[$option];
            } else {
                $cached_values = [$option => $values[$option]];
            }
        }
        AngieApplication::cache()->setByObject($for, 'config_options', $cached_values);

        return $find === $name ? $values : array_shift($values);
    }

    /**
     * Get values for a given parent object.
     *
     * @param  IConfigContext|DataObject $for
     * @param  bool                      $use_cache
     * @return array
     */
    public static function getValuesFor(array $names, IConfigContext $for, $use_cache = true)
    {
        $values = [];

        $cached_values = AngieApplication::cache()->getByObject($for, 'config_options');

        // check cached values
        if ($use_cache && is_array($cached_values)) {
            foreach ($names as $option) {
                if (array_key_exists($option, $cached_values)) {
                    $values[$option] = $cached_values[$option];
                    unset($names[$option]);
                }
            }
        }

        // get and set values that are not cached
        if (!empty($names) &&
            $rows = DB::execute(
                'SELECT name, value FROM config_option_values WHERE parent_type = ? AND parent_id = ? AND name IN (?)',
                self::getParentTypeByObject($for),
                $for->getId(),
                $names
            )
            ) {
            foreach ($rows as $row) {
                $name = $row['name'];
                $value = unserialize($row['value']);

                $values[$name] = $value;

                if (is_array($cached_values)) {
                    $cached_values[$name] = $values[$name];
                } else {
                    $cached_values = [$name => $values[$name]];
                }
            }
        }

        // if some provided config option doesn't exists in 'config_option_values' table,
        // try to find default value for it
        $non_exists_values = array_diff($names, array_keys($values));
        if (!empty($non_exists_values)) {
            foreach ($non_exists_values as $non_value) {
                try {
                    $default_non_value = self::getValue($non_value);
                } catch (ConfigOptionDnxError $e) {
                    $default_non_value = null;
                }

                $values[$non_value] = $default_non_value;

                if (is_array($cached_values)) {
                    $cached_values[$non_value] = $default_non_value;
                } else {
                    $cached_values = [$non_value => $default_non_value];
                }
            }
        }

        AngieApplication::cache()->setByObject($for, 'config_options', $cached_values);

        return $values;
    }

    /**
     * Return parent type based on object instance.
     *
     * @param  mixed             $object
     * @return mixed|string
     * @throws InvalidParamError
     */
    private static function getParentTypeByObject($object)
    {
        if ($object instanceof DataObject) {
            return $object->getModelName(false, true);
        } elseif (is_object($object)) {
            return get_class($object);
        } else {
            throw new InvalidParamError('object', $object, '$object is not an object');
        }
    }

    /**
     * Returns true if there is a value for given config options.
     *
     * @param  string                    $name
     * @param  IConfigContext|DataObject $for
     * @return bool
     */
    public static function hasValueFor($name, IConfigContext $for)
    {
        if (is_array($name)) {
            $options = array_unique($name);
        } else {
            $options = [$name];
        }

        $number_of_matching_options = (int) DB::executeFirstCell(
            'SELECT COUNT(*) AS "row_count" FROM `config_option_values` WHERE `name` = ? AND `parent_type` = ? AND `parent_id` = ?',
            $options,
            self::getParentTypeByObject($for),
            $for->getId()
        );

        return $number_of_matching_options == count($options);
    }

    /**
     * Set value for a given parent object.
     *
     * @param  string|string[]           $name
     * @param  IConfigContext|DataObject $for
     * @param  mixed                     $value
     * @return array|null
     * @throws Exception
     */
    public static function setValueFor($name, IConfigContext $for, $value = null)
    {
        try {
            DB::beginWork('Setting configuration option for object @ ' . __CLASS__);

            $cached_values = AngieApplication::cache()->getByObject($for, 'config_options');

            $to_set = is_array($name) ? $name : [$name => $value];

            $for_parent_type = self::getParentTypeByObject($for);
            $for_parent_id = $for->getId();

            foreach ($to_set as $k => $v) {
                if (self::exists($k, false)) {
                    $number_of_values = (int) DB::executeFirstCell(
                        "SELECT COUNT(*) AS 'row_count' FROM `config_option_values` WHERE `name` = ? AND `parent_type` = ? AND `parent_id` = ?",
                        $k,
                        $for_parent_type,
                        $for_parent_id
                    );

                    if ($number_of_values) {
                        if ($v === null) {
                            DB::execute(
                                'DELETE FROM `config_option_values` WHERE `name` = ? AND `parent_type` = ? AND `parent_id` = ?',
                                $v,
                                $for_parent_type,
                                $for_parent_id
                            );
                        } else {
                            DB::execute(
                                'UPDATE `config_option_values` SET `value` = ? WHERE `name` = ? AND `parent_type` = ? AND `parent_id` = ?',
                                serialize($v),
                                $k,
                                $for_parent_type,
                                $for_parent_id
                            );
                        }
                    } else {
                        DB::execute(
                            'REPLACE INTO `config_option_values` (`name`, `parent_type`, `parent_id`, `value`) VALUES (?, ?, ?, ?)',
                            $k,
                            $for_parent_type,
                            $for_parent_id,
                            serialize($v)
                        );
                    }

                    if ($v === null) {
                        if (is_array($cached_values) && isset($cached_values[$k])) {
                            unset($cached_values[$k]);
                        }
                    } else {
                        if (is_array($cached_values)) {
                            $cached_values[$k] = $v;
                        } else {
                            $cached_values = [$k => $v];
                        }
                    }
                } else {
                    throw new ConfigOptionDnxError($k);
                }
            }

            AngieApplication::cache()->setByObject($for, 'config_options', $cached_values);

            DB::commit('Configuration option values set for object @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to set configuration option values for object @ ' . __CLASS__);

            throw $e;
        }

        return $name === $to_set ? $to_set : $value; // Return what we have just set
    }

    // ---------------------------------------------------
    //  Management
    // ---------------------------------------------------

    /**
     * Remove all custom values for given option.
     *
     * @param string $name
     */
    public static function removeValues($name)
    {
        DB::execute('DELETE FROM config_option_values WHERE name IN (?)', $name);
        AngieApplication::cache()->clear();
    }

    /**
     * Remove all values for a given parent object.
     *
     * @param  mixed $specific
     * @return bool
     */
    public static function removeValuesFor(IConfigContext $for, $specific = null)
    {
        if ($specific) {
            DB::execute('DELETE FROM config_option_values WHERE name IN (?) AND parent_type = ? AND parent_id = ?', (array) $specific, self::getParentTypeByObject($for), $for->getId());
        } else {
            DB::execute('DELETE FROM config_option_values WHERE parent_type = ? AND parent_id = ?', self::getParentTypeByObject($for), $for->getId());
        }

        AngieApplication::cache()->removeByObject($for, 'config_options');
    }

    /**
     * Clone custom configuration options from source to target object.
     *
     * @throws Exception
     */
    public static function cloneValuesFor(IConfigContext $from, IConfigContext $to)
    {
        if ($rows = DB::execute('SELECT name, value FROM config_option_values WHERE parent_type = ? AND parent_id = ?', self::getParentTypeByObject($from), $from->getId())) {
            $escaped_parent_type = DB::escape(self::getParentTypeByObject($to));
            $escaped_parent_id = DB::escape($to->getId());

            try {
                DB::beginWork('Cloning custom config option values @ ' . __CLASS__);

                foreach ($rows as $row) {
                    DB::execute("REPLACE INTO config_option_values (name, parent_type, parent_id, value) VALUES (?, $escaped_parent_type, $escaped_parent_id, ?)", $row['name'], $row['value']);
                }

                DB::commit('Clonned custom config option values @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Failed to clone custom config option values @ ' . __CLASS__);

                throw $e;
            }
        }
    }

    /**
     * Return number of custom values for given option.
     *
     * @param  string $name
     * @param  mixed  $value
     * @param  array  $exclude
     * @return int
     */
    public static function countByValue($name, $value, $exclude = null)
    {
        $exclude_filter = '';

        if (is_foreachable($exclude)) {
            $exclude_filter = [];

            foreach ($exclude as $exclude_object) {
                $exclude_filter[] = DB::prepare('(parent_type = ? AND parent_id = ?)', self::getParentTypeByObject($exclude_object), $exclude_object->getId());
            }

            $exclude_filter = ' AND NOT (' . implode(' OR ', $exclude_filter) . ')';
        }

        return (int) DB::executeFirstCell('SELECT COUNT(*) AS "row_count" FROM config_option_values WHERE name = ? AND value = ? ' . $exclude_filter, $name, serialize($value));
    }

    // ---------------------------------------------------
    //  Utility
    // ---------------------------------------------------

    /**
     * Remove all custom values by $name and $value.
     *
     * This method is useful when we need to clean up custom values when
     * something system wide is changed (language or filter is removed etc)
     *
     * @param string $name
     * @param mixed  $value
     */
    public static function removeByValue($name, $value)
    {
        DB::execute('DELETE FROM `config_option_values` WHERE `name` = ? AND `value` = ?', $name, serialize($value));
        AngieApplication::cache()->clearModelCache();
    }

    /**
     * Define new option.
     *
     * @param  string $name
     * @param  mixed  $default_value
     * @throws Error
     */
    public static function addOption($name, $default_value = null)
    {
        if (empty($name)) {
            throw new Error('Configuration option name is required');
        }

        DB::execute('REPLACE INTO config_options (name, value) VALUES (?, ?)', $name, serialize($default_value));
        AngieApplication::cache()->remove('config_options');
    }

    /**
     * Remove option definition.
     *
     * @param string $name
     */
    public static function removeOption($name)
    {
        DB::transact(function () use ($name) {
            DB::execute('DELETE FROM config_options WHERE name = ?', $name);
            DB::execute('DELETE FROM config_option_values WHERE name = ?', $name);

            AngieApplication::cache()->remove('config_options');
            AngieApplication::cache()->clearModelCache();
        }, 'Removing config option');
    }

    /**
     * Protect access or update of a particular configuration option value.
     *
     * @param array        $options
     * @param Closure|null $access
     * @param Closure|null $update
     */
    public static function protect($options, $access = null, $update = null)
    {
        $options = (array) $options;

        foreach ($options as $option) {
            self::$protected_options[$option] = [$access, $update];
        }
    }

    /**
     * Return true if $user can access value of $option.
     *
     * @param  string $option
     * @return bool
     */
    public static function canAccess($option, User $user)
    {
        if (empty(self::$protected_options_event_triggered)) {
            Angie\Events::trigger('on_protected_config_options');
            self::$protected_options_event_triggered = true;
        }

        if (isset(self::$protected_options[$option]) && self::$protected_options[$option][0] instanceof Closure) {
            return self::$protected_options[$option][0]->__invoke($user);
        }

        return true;
    }

    /**
     * Return true if $user can update value of $option.
     *
     * @param  string $option
     * @return bool
     */
    public static function canUpdate($option, User $user)
    {
        if (empty(self::$protected_options_event_triggered)) {
            Angie\Events::trigger('on_protected_config_options');
            self::$protected_options_event_triggered = true;
        }

        if (isset(self::$protected_options[$option]) && self::$protected_options[$option][1] instanceof Closure) {
            return self::$protected_options[$option][1]->__invoke($user);
        }

        return true;
    }
}
