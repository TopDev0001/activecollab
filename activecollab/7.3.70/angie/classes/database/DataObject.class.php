<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Models\IdentifiableInterface;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use Angie\Events;

/**
 * Return data object cache key based on given parameters.
 *
 * @param  string            $model_name
 * @param  int               $id
 * @param  string|array|null $subnamespace
 * @return array
 */
function get_data_object_cache_key($model_name, $id, $subnamespace = null)
{
    $key = [
        'models',
        $model_name,
        $id,
    ];

    if (!empty($subnamespace)) {
        return array_merge($key, (array) $subnamespace);
    }

    return $key;
}

abstract class DataObject implements IdentifiableInterface, IEtag, JsonSerializable
{
    use IEtagImplementation;

    protected string $table_name;
    protected array $fields;
    protected array $default_field_values = [];
    protected array $primary_key = [];
    protected ?string $auto_increment = null;
    protected ?array $protect = null;
    protected ?array $accept = null;

    // ---------------------------------------------------
    //  Internals, not overridable
    // ---------------------------------------------------

    private bool $is_new = true;
    private bool $is_loading = false;
    private array $values = [];
    private array $old_values = [];
    private array $modified_fields = [];
    private ?array $primary_key_updated = null;

    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

    private array $event_handlers = [];
    private bool $is_untouchable = false;

    public function __construct()
    {
        $this->__configure();
    }

    protected function __configure(): void
    {
    }

    /**
     * Load object by specific ID.
     *
     * @param  mixed $id
     * @return bool
     */
    public function load($id)
    {
        if (empty($id)) {
            throw new InvalidParamError('id', $id, '$id is expected to be a valid object ID');
        }

        $key = $this->getCacheKey((int) $id);

        $row = AngieApplication::cache()->isCached($key) ? AngieApplication::cache()->get($key) : null;

        if (empty($row)) {
            $fields = $this->getFields();
            $table_name = $this->getTableName();
            $where = $this->getWherePartById($id);

            $row = AngieApplication::cache()->get(
                $key,
                function () use ($fields, $table_name, $where) {
                    return DB::executeFirstRow(
                        sprintf(
                            'SELECT %s FROM %s WHERE %s LIMIT 0, 1',
                            implode(', ', $fields),
                            $table_name,
                            $where
                        )
                    );
                }
            );
        }

        if (is_array($row)) {
            return $this->loadFromRow($row);
        }

        return false;
    }

    public function getCacheKey(int $id = null): array
    {
        return get_data_object_cache_key(
            $this->getModelName(true),
            $id ?? $this->getId()
        );
    }

    abstract public function getModelName(
        bool $underscore = false,
        bool $singular = false
    ): string;

    public function getType()
    {
        return get_class($this);
    }

    /**
     * Return list of fields.
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Return value of table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->table_name;
    }

    /**
     * Return where part of query.
     *
     * @param  mixed  $value Array of values if we need them
     * @return string
     */
    public function getWherePartById($value = null)
    {
        $pks = $this->getPrimaryKey();

        if (count($pks) > 1) {
            $where = [];
            foreach ($pks as $field) {
                $field_value = isset($value[$field]) ? $value[$field] : $this->getFieldValue($field);
                $where[] = $field . ' = ' . DB::escape($field_value);
            }

            return count($where) > 1 ? implode(' AND ', $where) : $where[0];
        } else {
            $pk = $pks[0];
            $pk_value = is_array($value) ? $value[$pk] : $value;

            return $pk . ' = ' . DB::escape($pk_value);
        }
    }

    // ---------------------------------------------------
    //  CRUD methods
    // ---------------------------------------------------

    /**
     * Return primary key columns.
     *
     * @return array
     */
    public function getPrimaryKey()
    {
        return $this->primary_key;
    }

    /**
     * Return value of specific field and typecast it...
     *
     * @param  string $field   Field value
     * @param  mixed  $default Default value that is returned in case of any error
     * @return mixed
     */
    public function getFieldValue($field, $default = null)
    {
        if (empty($this->values[$field]) && !array_key_exists($field, $this->values)) {
            return empty($this->default_field_values[$field]) && !array_key_exists($field, $this->default_field_values) ? $default : $this->default_field_values[$field];
        } else {
            return $this->values[$field];
        }
    }

    /**
     * Load data from database row.
     *
     * If $cache_row is set to true row data will be added to cache
     *
     * @param  array $row
     * @param  bool  $cache_row
     * @return bool
     */
    public function loadFromRow($row, $cache_row = false)
    {
        if ($row && is_array($row)) {
            $this->is_loading = true;

            foreach ($row as $k => $v) {
                if ($this->fieldExists($k)) {
                    $this->setFieldValue($k, $v);
                }
            }

            if ($cache_row) {
                AngieApplication::cache()->set(
                    $this->getCacheKey((int) $row['id']),
                    $row
                );
            }

            $this->setLoaded(true);
            $this->is_loading = false;
            $this->resetModifiedFlags();
        } else {
            $this->is_loading = false;
            throw new InvalidParamError('row', $row, '$row is expected to be loaded database row');
        }

        return true;
    }

    /**
     * Check if specific field is defined.
     *
     * @param  string $field Field name
     * @return bool
     */
    public function fieldExists($field)
    {
        return in_array($field, $this->fields);
    }

    /**
     * Set specific field value.
     *
     * Set value of the $field. This function will make sure that everything
     * runs fine - modifications are saved, in case of primary key old value
     * will be remembered in case we need to update the row and so on
     *
     * @param  mixed $value
     * @return mixed
     */
    public function setFieldValue(string $field, $value)
    {
        if (in_array($field, $this->fields)) {
            if (!$this->isLoading()) {
                $this->triggerEvent('on_prepare_field_value_before_set', [$field, &$value]);
            }

            if (!array_key_exists($field, $this->values) || ($this->values[$field] !== $value)) {
                // If we are loading object there is no need to remember if this field
                // was modified, if PK has been updated and old value. We just skip that
                if (!$this->is_loading) {
                    if (isset($this->values[$field])) {
                        $old_value = $this->values[$field]; // Remember old value
                    }

                    // Save primary key value. Also make sure that only the first PK value is
                    // saved as old. Not to save second value on third modification ;)
                    if ($this->isPrimaryKey($field) && empty($this->primary_key_updated[$field])) {
                        if (!is_array($this->primary_key_updated)) {
                            $this->primary_key_updated = [];
                        }
                        $this->primary_key_updated[$field] = true;
                    }

                    // Save old value if we haven't done that already
                    if (isset($old_value) && !isset($this->old_values[$field])) {
                        $this->old_values[$field] = $old_value;
                    }

                    $this->addModifiedField($field); // Remember that this file was modified
                }

                $this->values[$field] = $value;
            }

            return $value;
        } else {
            throw new InvalidParamError('field', $field, "Field '$field' does not exist");
        }
    }

    // ---------------------------------------------------
    //  Flags
    // ---------------------------------------------------

    /**
     * Returns true if this object is in the middle of hydration process
     * (loading values from database row).
     *
     * @return bool
     */
    public function isLoading()
    {
        return $this->is_loading;
    }

    /**
     * Trigger an internal event.
     *
     * @param string $event
     * @param array  $event_parameters
     */
    protected function triggerEvent($event, $event_parameters = null)
    {
        if (isset($this->event_handlers[$event])) {
            foreach ($this->event_handlers[$event] as $handler) {
                if (empty($event_parameters)) {
                    $event_parameters = [];
                }

                if ($handler instanceof Closure) {
                    call_user_func_array($handler, $event_parameters);
                } else {
                    call_user_func_array([$this, $handler], $event_parameters);
                }
            }
        }
    }

    /**
     * Check if selected field is primary key.
     *
     * @param  string $field Field that need to be checked
     * @return bool
     */
    public function isPrimaryKey($field)
    {
        return in_array($field, $this->primary_key);
    }

    /**
     * Add new modified field.
     *
     * @param string $field Field that need to be added
     */
    public function addModifiedField($field)
    {
        if (!in_array($field, $this->modified_fields)) {
            $this->modified_fields[] = $field;
        }
    }

    /**
     * Set loaded stamp value.
     *
     * @param bool $value New value
     */
    public function setLoaded($value)
    {
        $this->is_new = !$value;
    }

    // ---------------------------------------------------
    //  Etag
    // ---------------------------------------------------

    /**
     * Reset modification idicators.
     *
     * Useful when you use setXXX functions but you dont want to modify
     * anything (just loading data from database in fresh object using
     * setFieldValue function)
     */
    public function resetModifiedFlags()
    {
        $this->modified_fields = [];
        $this->old_values = [];
        $this->primary_key_updated = null;
    }

    /**
     * Returns true if $var is the same object this object is.
     *
     * Comparison is done on class - PK values for loaded objects, or as simple
     * object comparison in case objects are not saved and loaded
     *
     * @param  DataObject|mixed $var
     * @return bool
     */
    public function is($var)
    {
        if ($var instanceof self) {
            if ($this->isLoaded()) {
                return $var->isLoaded() && get_class($this) == get_class($var) && $this->getPrimaryKeyValue() == $var->getPrimaryKeyValue();
            } else {
                foreach ($this->getFields() as $field_name) {
                    if (!$var->fieldExists($field_name) || $this->getFieldValue($field_name) !== $var->getFieldValue($field_name)) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if this object have row in database.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return !$this->is_new;
    }

    // ---------------------------------------------------
    //  Fields
    // ---------------------------------------------------

    /**
     * Return value of primary key.
     *
     * @return array
     */
    public function getPrimaryKeyValue()
    {
        if ($this->primary_key && count($this->primary_key)) {
            $ret = [];
            foreach ($this->primary_key as $pk) {
                $ret[$pk] = $this->getFieldValue($pk);
            }

            return count($ret) > 1 ? $ret : $ret[$this->primary_key[0]];
        }

        return null;
    }

    /**
     * Return object attributes.
     *
     * This function will return array of attribute name -> attribute value pairs
     * for this specific project
     *
     * @return array
     */
    public function getAttributes()
    {
        $field_values = [];

        foreach ($this->fields as $field) {
            $field_values[$field] = $this->getFieldValue($field);
        }

        return $field_values;
    }

    /**
     * Set object attributes / properties. This function will take hash and set
     * value of all fields that she finds in the hash.
     *
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        if (empty($attributes)) {
            $attributes = [];
        }

        $this->triggerEvent('on_set_attributes', [&$attributes]);

        foreach ($attributes as $k => $v) {
            if (is_array($this->protect) && (in_array($k, $this->protect) || in_array($k, $this->protect))) {
                continue; // field is in list of protected fields
            }
            if (is_array($this->accept) && !(in_array($k, $this->accept) || in_array($k, $this->protect))) {
                continue; // not in list of acceptable fields
            }
            if ($this->fieldExists($k)) {
                $this->setFieldValue($k, $attributes[$k]);
            } else {
                $this->setAttribute($k, $v);
            }
        }
    }

    /**
     * Set non-field value during DataManager::create() and DataManager::update() calls.
     *
     * @param string $attribute
     * @param mixed  $value
     */
    public function setAttribute($attribute, $value)
    {
        $this->triggerEvent('on_set_attribute', [$attribute, $value]);
    }

    /**
     * Delete specific object (and related objects if neccecery).
     *
     * @param bool $bulk
     */
    public function delete($bulk = false)
    {
        if ($this->isLoaded()) {
            $cache_id = $this->getCacheKey();

            $this->triggerEvent('on_before_delete', [$bulk]);

            Events::trigger('on_before_object_deleted', [&$this, $bulk]);

            DB::execute('DELETE FROM ' . $this->getTableName() . ' WHERE ' . $this->getWherePartById($this->getPrimaryKeyValue()));

            $this->is_new = true;

            AngieApplication::cache()->remove($cache_id);

            $this->triggerEvent('on_after_delete', [$bulk]);

            Events::trigger('on_object_deleted', [&$this, $bulk]);
        }
    }

    /**
     * Create a copy of this object and optionally save it.
     *
     * @param  bool       $save
     * @return DataObject
     */
    public function copy($save = false)
    {
        $object_class = get_class($this);

        /** @var DataObject $copy */
        $copy = new $object_class();

        foreach ($this->fields as $field) {
            if (!in_array($field, $this->primary_key)) {
                $copy->setFieldValue($field, $this->getFieldValue($field));
            }
        }

        if ($save) {
            $copy->save();
        }

        return $copy;
    }

    /**
     * Save object into database (insert or update).
     *
     * If this object does not pass validation error object with all model errors
     * will be returned (object of ValidationErrors class)
     */
    public function save()
    {
        if ($this->isNew()) {
            foreach ($this->default_field_values as $field_name => $field_value) {
                if (empty($this->values[$field_name]) && !array_key_exists($field_name, $this->values)) {
                    $this->setFieldValue($field_name, $field_value);
                }
            }
        }

        $errors = new ValidationErrors();
        $errors->setObject($this);

        Events::trigger('on_before_object_validation', [&$this]);
        $this->validate($errors);

        Events::trigger('on_after_object_validation', [&$this, &$errors]);

        if ($errors->hasErrors()) {
            throw $errors;
        }

        if ($this->isNew()) {
            $this->doInsert();
        } else {
            $this->doUpdate();
        }

        AngieApplication::cache()->removeByObject($this);
    }

    /**
     * Return value of $is_new variable.
     *
     * @return bool
     */
    public function isNew()
    {
        return (bool) $this->is_new;
    }

    /**
     * Validate object properties before object is saved.
     *
     * This method is called before the item is saved and can be used to fetch
     * errors in data before we really save it database. $errors is instance of
     * ValidationErrors class that is used for error collection. If collection
     * is empty object is considered valid and save process will continue
     */
    public function validate(ValidationErrors & $errors)
    {
        $this->triggerEvent('on_validate', [&$errors]);
    }

    /**
     * Insert record in the database.
     */
    private function doInsert()
    {
        DB::execute($this->getInsertSQL());

        if (($this->auto_increment !== null) && (!isset($this->values[$this->auto_increment]) || !$this->values[$this->auto_increment])) {
            $this->values[$this->auto_increment] = DB::lastInsertId();
        }
        $this->resetModifiedFlags();
        $this->setLoaded(true);
    }

    /**
     * Prepare insert query.
     *
     * @return string
     */
    public function getInsertSQL()
    {
        $fields = [];
        $values = [];

        foreach ($this->values as $field_name => $field_value) {
            if ($this->fieldExists($field_name)) {
                $fields[] = $field_name;
                $values[] = DB::escape($field_value);
            }
        }

        return 'INSERT INTO ' . $this->getTableName() . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
    }

    /**
     * Update database record.
     */
    private function doUpdate()
    {
        $sql = $this->getUpdateSQL();

        if ($sql) {
            DB::execute($sql);

            $this->resetModifiedFlags();
            $this->setLoaded(true);
        }
    }

    /**
     * Prepare update query.
     *
     * @return string
     */
    public function getUpdateSQL()
    {
        $fields = [];

        if (!count($this->modified_fields)) {
            return null;
        }

        foreach ($this->fields as $field_name) {
            if ($this->isModifiedField($field_name)) {
                $fields[] = $field_name . ' = ' . DB::escape($this->values[$field_name]);
            }
        }

        if (is_array($this->primary_key_updated)) {
            $pks = $this->getPrimaryKey();
            $old = [];

            foreach ($pks as $pk) {
                $old[$pk] = isset($this->old_values[$pk]) ? $this->old_values[$pk] : $this->getFieldValue($pk);
            }

            if (count($old) && $this->exists($old)) {
                return sprintf('UPDATE %s SET %s WHERE %s', $this->getTableName(), implode(', ', $fields), $this->getWherePartById($old));
            } else {
                return $this->getInsertSQL();
            }
        } else {
            return sprintf('UPDATE %s SET %s WHERE %s', $this->getTableName(), implode(', ', $fields), $this->getWherePartById($this->getPrimaryKeyValue()));
        }
    }

    /**
     * Returns true if specific field is modified.
     *
     * @param  string $field
     * @return bool
     */
    public function isModifiedField($field)
    {
        return in_array($field, $this->modified_fields);
    }

    // ---------------------------------------------------
    //  Database interaction
    // ---------------------------------------------------

    /**
     * Check if specific row exists in database.
     *
     * @param  mixed $id
     * @return bool
     */
    public function exists($id)
    {
        return (bool) DB::executeFirstCell("SELECT count(*) AS 'row_count' FROM " . $this->getTableName() . ' WHERE ' . $this->getWherePartById($id));
    }

    /**
     * Set new stamp value.
     *
     * @param bool $value New value
     */
    public function setNew($value)
    {
        $this->is_new = (bool) $value;
    }

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->canBeTagged() && ($this->tag === false || empty($use_cache))) {
            $timestamp = $this->getFieldValue('updated_on') instanceof DateTimeValue ? $this->getFieldValue('updated_on')->toMySQL() : '-- unknown --';

            $this->tag = '"' . implode(
                ',',
                [
                    APPLICATION_VERSION,
                    'object',
                    $this->getModelName(),
                    $this->getId(),
                    $user->getEmail(),
                    sha1(APPLICATION_UNIQUE_KEY . $timestamp),
                ]
            ) . '"';
        }

        return $this->tag;
    }

    /**
     * Return true if this object can be tagged and cached on client side.
     *
     * @return bool|null
     */
    public function canBeTagged()
    {
        return $this->fieldExists('updated_on');
    }

    /**
     * Return array of modified fields.
     *
     * @return array
     */
    public function getModifiedFields()
    {
        return $this->modified_fields;
    }

    /**
     * Check if this object has modified columns.
     *
     * @return bool
     */
    public function isModified()
    {
        return (bool) count($this->modified_fields);
    }

    /**
     * Revert field to old value.
     *
     * @param $field
     */
    public function revertField($field)
    {
        if ($this->isModifiedField($field)) {
            // revert field value
            $this->setFieldValue($field, $this->getOldFieldValue($field));

            // remove modified flag
            if (($key = array_search($field, $this->modified_fields)) !== false) {
                unset($this->modified_fields[$field]);
            }
        }
    }

    /**
     * Return all field value.
     *
     * @param  string $field
     * @return mixed
     */
    public function getOldFieldValue($field)
    {
        return isset($this->old_values[$field]) ? $this->old_values[$field] : null;
    }

    // ---------------------------------------------------
    //  Events
    // ---------------------------------------------------

    /**
     * Calculate fields checksum.
     *
     * @return string
     */
    public function getFieldsChecksum()
    {
        return md5(implode(' ', $this->values));
    }

    /**
     * Return old field values, before fields were updated.
     *
     * @return array
     */
    public function getOldValues()
    {
        return $this->old_values;
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = [
            'id' => $this->getId(),
            'class' => get_class($this),
            'url_path' => $this instanceof RoutingContextInterface && $this->isLoaded() ? $this->getUrlPath() : '#',
        ];

        if ($this->fieldExists('name')) {
            $result['name'] = $this->getName();
        }

        $this->triggerEvent('on_json_serialize', [&$result]);

        return $result;
    }

    // ---------------------------------------------------
    //  Describe
    // ---------------------------------------------------

    /**
     * Return name.
     *
     * @return string
     */
    public function getName()
    {
        return '-- unknown --';
    }

    /**
     * Describe single.
     */
    public function describeSingleForFeather(array & $result)
    {
        $this->triggerEvent('on_describe_single', [&$result]);
    }

    // ---------------------------------------------------
    //  Touch
    // ---------------------------------------------------

    /**
     * Run $callback while this object is untouchable.
     */
    public function untouchable(callable $callback)
    {
        $original_untouchable = $this->is_untouchable;

        $this->is_untouchable = true;

        call_user_func($callback);

        $this->is_untouchable = $original_untouchable;
    }

    /**
     * Refresh object's updated_on flag.
     *
     * @param User|null  $by
     * @param array|null $additional
     * @param bool       $save
     */
    public function touch($by = null, $additional = null, $save = true)
    {
        if ($this->is_untouchable) {
            return;
        }

        $this->triggerEvent('on_before_touch', [$by, $additional, $save]);

        if ($this instanceof IUpdatedBy && $by instanceof IUser) {
            $this->setUpdatedBy($by);
        }

        if ($this instanceof IUpdatedOn) {
            $this->setUpdatedOn(DateTimeValue::now());
        }

        if ($save) {
            $this->save();
        }

        $this->triggerEvent('on_after_touch', [$by, $additional, $save]);
    }

    public function touchParentOnPropertyChange(): ?array
    {
        return null;
    }

    /**
     * Validates presence of specific field.
     *
     * In case of string value is trimmed and compared with the empty string. In
     * case of any other type empty() function is used. If $min_value argument is
     * provided value will also need to be larger or equal to it
     * (validateMinValueOf validator is used)
     *
     * @param  string  $field     Field name
     * @param  mixed   $min_value
     * @param  Closure $modifier
     * @return bool
     */
    public function validatePresenceOf($field, $min_value = null, $modifier = null)
    {
        $value = $this->getFieldValue($field);

        if ($modifier && ($modifier instanceof Closure || function_exists($modifier))) {
            $value = call_user_func($modifier, $value);
        }

        if (is_string($value)) {
            if (trim($value)) {
                return $min_value === null || $this->validateMinValueOf($field, $min_value);
            }

            return false;
        }

        if (empty($value)) {
            return false;
        }

        return $min_value === null || $this->validateMinValueOf($field, $min_value);
    }

    /**
     * This validator will return true if $value is unique (there is no row with such value in that field).
     *
     * @param  string $field
     * @return bool
     */
    public function validateUniquenessOf($field)
    {
        // Don't do COUNT(*) if we have one PK column
        $escaped_pk = is_array($pk_fields = $this->getPrimaryKey()) ? '*' : $pk_fields;

        // Get columns
        $fields = func_get_args();
        if (!is_array($fields) || count($fields) < 1) {
            return true;
        }

        // Check if we have existsing columns
        foreach ($fields as $field) {
            if (!$this->fieldExists($field)) {
                return false;
            }
        }

        // Get where parets
        $where_parts = [];
        foreach ($fields as $field) {
            $where_parts[] = sprintf('%s = %s', $field, DB::escape($this->values[$field]));
        }

        // If we have new object we need to test if there is any other object
        // with this value. Else we need to check if there is any other EXCEPT
        // this one with that value
        if ($this->isNew()) {
            $sql = sprintf(
                "SELECT COUNT(%s) AS 'row_count' FROM %s WHERE %s",
                $escaped_pk,
                $this->getTableName(),
                implode(' AND ', $where_parts)
            );
        } else {
            // Prepare PKs part...
            $pks = $this->getPrimaryKey();
            $pk_values = [];
            if (is_array($pks)) {
                foreach ($pks as $pk) {
                    if (isset($this->primary_key_updated[$pk]) && $this->primary_key_updated[$pk]) {
                        $primary_key_value = $this->old_values[$pk];
                    } else {
                        $primary_key_value = $this->values[$pk];
                    }
                    $pk_values[] = sprintf('%s <> %s', $pk, DB::escape($primary_key_value));
                }
            }

            // Prepare SQL
            $sql = sprintf(
                "SELECT COUNT(%s) AS 'row_count' FROM %s WHERE (%s) AND (%s)",
                $escaped_pk,
                $this->getTableName(),
                implode(' AND ', $where_parts),
                implode(' AND ', $pk_values)
            );
        }

        return DB::executeFirstCell($sql) < 1;
    }

    /**
     * Valicate minimal value of specific field.
     *
     * If string minimal lenght is checked (string is trimmed before it is
     * compared). In any other case >= operator is used
     *
     * @param  string $field
     * @param  int    $min
     * @return bool
     */
    public function validateMinValueOf($field, $min)
    {
        if ($this->fieldExists($field)) {
            $value = $this->getFieldValue($field);

            if (is_string($value)) {
                return strlen_utf(trim($value)) >= $min;
            }

            return $value >= $min;
        }

        return false;
    }

    /**
     * Validate max value of specific field. If that field is string time
     * max lenght will be validated.
     *
     * @param  string $field
     * @param  int    $max
     * @return bool
     */
    public function validateMaxValueOf($field, $max)
    {
        if ($this->fieldExists($field)) {
            $value = $this->getFieldValue($field);

            if (is_string($value)) {
                return strlen_utf(trim($value)) <= $max;
            }

            return $value <= $max;
        }

        return false;
    }

    /**
     * Valicate that field value is in range of $min and $max.
     *
     * If field value is string, lenght is checked (string is trimmed before it is
     * compared). In any other case <= and >= operator is used
     *
     * @param  string $field
     * @param  int    $min
     * @param  int    $max
     * @return bool
     */
    public function validateValueInRange($field, $min, $max)
    {
        if ($this->fieldExists($field)) {
            $value = $this->getFieldValue($field);

            if (is_string($value)) {
                $string_length = strlen_utf(trim($value));

                return $string_length >= $min && $string_length <= $max;
            } else {
                return $value >= $min && $value <= $max;
            }
        }

        return false;
    }

    protected function registerEventHandler(string $event, callable $handler): void
    {
        if (empty($this->event_handlers[$event])) {
            $this->event_handlers[$event] = [];
        }

        $this->event_handlers[$event][] = $handler;
    }
}
