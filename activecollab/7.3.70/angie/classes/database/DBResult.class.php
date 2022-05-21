<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Abstraction of database query result.
 *
 * @package angie.library.database
 */
abstract class DBResult implements IteratorAggregate, ArrayAccess, Countable, JsonSerializable
{
    // Casting modes
    const CAST_INT = 'int';
    const CAST_FLOAT = 'float';
    const CAST_STRING = 'string';
    const CAST_BOOL = 'bool';
    const CAST_DATE = 'date';
    const CAST_DATETIME = 'datetime';

    /**
     * Cursor position.
     *
     * @var int
     */
    protected $cursor_position = 0;

    /**
     * Current row, set by.
     *
     * @var int|DataObject
     */
    protected $current_row;

    /**
     * Database result resource.
     *
     * @var resource
     */
    protected $resource;

    /**
     * Return mode.
     *
     * @var int
     */
    protected $return_mode;

    /**
     * Name of the class or field for return, if this result is returning
     * objects based on rows.
     *
     * @var string
     */
    protected $return_class_or_field;

    /**
     * Field casting rules.
     *
     * @var array
     */
    protected $casting = ['id' => self::CAST_INT, 'row_count' => self::CAST_INT];

    /**
     * Construct DBResult from resource.
     *
     * @param  mixed    $resource
     * @param  int      $return_mode
     * @param  string   $return_class_or_field
     * @return DBResult
     */
    public function __construct($resource, $return_mode = DB::RETURN_ARRAY, $return_class_or_field = null)
    {
        if ($this->isValidResource($resource)) {
            $this->resource = $resource;
            $this->return_mode = $return_mode;
            $this->return_class_or_field = $return_class_or_field;
        } else {
            throw new InvalidParamError('resource', $resource, '$resource is expected to be valid DB result resource');
        }
    }

    /**
     * Returns true if $resource is valid result resource.
     *
     * @param  mixed $resource
     * @return bool
     */
    protected function isValidResource($resource)
    {
        return is_resource($resource);
    }

    /**
     * Free result on destruction.
     */
    public function __destruct()
    {
        $this->free();
    }

    /**
     * Free resource when we are done with this result.
     *
     * @return bool
     */
    abstract public function free();

    /**
     * Return resource.
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Return cursor position.
     *
     * @return int
     */
    public function getCursorPosition()
    {
        return $this->cursor_position;
    }

    public function toArray(): array
    {
        $result = [];

        foreach ($this as $row) {
            $result[] = $row;
        }

        return $result;
    }

    public function toMap(string $key_field, string $value_field): array
    {
        $result = [];

        foreach ($this as $row) {
            if (isset($row[$key_field]) && isset($row[$value_field])) {
                $result[$row[$key_field]] = $row[$value_field];
            }
        }

        return $result;
    }

    public function toArrayIndexedBy(string $field_or_getter): array
    {
        $result = [];

        foreach ($this as $row) {
            if ($this->return_mode == DB::RETURN_ARRAY) {
                $result[$row[$field_or_getter]] = $row;
            } else {
                $result[$row->$field_or_getter()] = $row;
            }
        }

        return $result;
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $records = [];

        if ($this->count()) {
            foreach ($this as $record) {
                if ($record instanceof JsonSerializable) {
                    $records[] = AngieApplication::cache()->getByObject(
                        $record,
                        'json_serialize',
                        function () use ($record) {
                            return $record->jsonSerialize();
                        }
                    );
                } else {
                    $records[] = $record;
                }
            }
        }

        return $records;
    }

    /**
     * Set casting options.
     *
     * Several options are possible:
     *
     * // Set casting for a signle field
     * $result->setCasting('company_id', DBResult::CAST_INT);
     *
     * // Set casting for multiple fields
     * $result->setCasting(array(
     *   'company_id' => DBResult::CAST_INT,
     *   'created_on' => DBResult::CAST_DATE,
     * ));
     *
     * // Reset casting settings for specific field
     * $result->setCasting('company_id', null);
     *
     * // Reset casting settings for multiple fields
     * $result->setCasting(array(
     *   'company_id' => null,
     *   'created_on' => null,
     * ));
     *
     * // Reset casting for all fields
     * $result->setCastign(null);
     *
     * @param string|array $field
     * @param mixed        $cast
     */
    public function setCasting($field, $cast = null)
    {
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                if ($v === null) {
                    if (isset($this->casting[$k])) {
                        unset($this->casting[$k]);
                    }
                } else {
                    $this->casting[$k] = $v;
                }
            }
        } elseif ($field === null) {
            $this->casting = [];
        } else {
            if ($cast === null) {
                if (isset($this->casting[$field])) {
                    unset($this->casting[$field]);
                }
            } else {
                $this->casting[$field] = $cast;
            }
        }
    }

    /**
     * Set result to return objects by class name.
     *
     * @param string $class_name
     */
    public function returnObjectsByClass($class_name)
    {
        $this->return_mode = DB::RETURN_OBJECT_BY_CLASS;

        $this->return_class_or_field = $class_name;
    }

    /**
     * Set result to load objects of class based on filed value.
     *
     * @param string $field_name
     */
    public function returnObjectsByField($field_name)
    {
        $this->return_mode = DB::RETURN_OBJECT_BY_FIELD;

        $this->return_class_or_field = $field_name;
    }

    /**
     * Check if $offset exists.
     *
     * @param  string $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $offset >= 0 && $offset < $this->count();
    }

    /**
     * Return value at $offset.
     *
     * @param  string $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->getRowAt($offset);
    }

    // ---------------------------------------------------
    //  Casting
    // ---------------------------------------------------

    /**
     * Return row at $row_num.
     *
     * This function loads row at given position. When row is loaded, cursor is
     * set for the next row
     *
     * @param  int   $row_num
     * @return mixed
     */
    public function getRowAt($row_num)
    {
        if ($this->seek($row_num)) {
            $this->next();

            return $this->getCurrentRow();
        }

        return null;
    }

    /**
     * Set cursor to given row.
     *
     * @param int $row_num
     */
    abstract public function seek($row_num);

    // ---------------------------------------------------
    //  Return mode
    // ---------------------------------------------------

    /**
     * Return next record in result set.
     *
     * @return array
     */
    abstract public function next();

    /**
     * Return current row.
     *
     * @return mixed
     */
    public function getCurrentRow()
    {
        return $this->current_row;
    }

    // ---------------------------------------------------
    //  Interface implementations
    // ---------------------------------------------------

    /**
     * Set current row.
     *
     * @param array $row
     */
    protected function setCurrentRow($row)
    {
        switch ($this->return_mode) {
            // Set object based on class name that we got in constructor
            case DB::RETURN_OBJECT_BY_CLASS:
                $class_name = $this->return_class_or_field;

                $this->current_row = new $class_name();
                $this->current_row->loadFromRow($row);
                break;

            // Set object based on class name from field
            case DB::RETURN_OBJECT_BY_FIELD:
                $class_name = $row[$this->return_class_or_field];

                $this->current_row = new $class_name();
                $this->current_row->loadFromRow($row);
                break;

            // Just return array
            default:
                $this->current_row = $row;

                foreach ($this->current_row as $k => $v) {
                    $this->current_row[$k] = $this->cast($k, $v);
                }
        }
    }

    /**
     * Cast field value to proper value.
     *
     * If $value is NULL, it will always be returned as NULL. If no casting
     * settings exist for the field, original $value will be returned
     *
     * @param  string                                        $field
     * @param  mixed                                         $value
     * @return bool|DateTimeValue|DateValue|float|int|string
     */
    protected function cast($field, $value)
    {
        if (empty($this->casting[$field])) {
            if (str_ends_with($field, '_id')) {
                $this->casting[$field] = self::CAST_INT;
            } elseif (str_starts_with($field, 'is_')) {
                $this->casting[$field] = self::CAST_BOOL;
            }
        }

        if (empty($this->casting) || $value === null || !isset($this->casting[$field])) {
            return $value;
        } else {
            if ($this->casting[$field] instanceof Closure) {
                return $this->casting[$field]->__invoke($value);
            } else {
                switch ($this->casting[$field]) {
                    case self::CAST_INT:
                        return (int) $value;
                    case self::CAST_FLOAT:
                        return (float) $value;
                    case self::CAST_STRING:
                        return (string) $value;
                    case self::CAST_BOOL:
                        return (bool) $value;
                    case self::CAST_DATE:
                        return new DateValue($value);
                    case self::CAST_DATETIME:
                        return new DateTimeValue($value);
                    default:
                        return $value;
                }
            }
        }
    }

    /**
     * Set value at $offset.
     *
     * @param string $offset
     * @param mixed  $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new NotImplementedError(__METHOD__, 'DB results are read only!');
    }

    /**
     * Unset value at $offset.
     *
     * @param string $offset
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new NotImplementedError(__METHOD__, 'DB results are read only!');
    }

    /**
     * Returns an iterator for for this object, for use with foreach.
     *
     * @return ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new DBResultIterator($this);
    }
}
