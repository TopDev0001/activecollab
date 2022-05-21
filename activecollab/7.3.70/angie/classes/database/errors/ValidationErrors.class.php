<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Container of multiple validation errors.
 *
 * @package angie.library.errors
 */
class ValidationErrors extends Error
{
    // Any field
    const ANY_FIELD = '-- any --';

    /**
     * Object instance.
     *
     * @var DataObject
     */
    private $object;

    /**
     * Errors array.
     *
     * @var array
     */
    private $errors = [];

    /**
     * Construct the FormErrors.
     *
     * @param array  $errors
     * @param string $message
     */
    public function __construct($errors = null, $message = null)
    {
        if ($message === null) {
            $message = 'Validation failed';
        }

        if (is_array($errors)) {
            foreach ($errors as $k => $error) {
                $field = is_numeric($k) ? null : $k;
                if (is_array($error)) {
                    foreach ($error as $single_error) {
                        $this->addError($single_error, $field);
                    }
                } elseif ($error) {
                    $this->addError($error, $field);
                }
            }
        }

        parent::__construct($message, ['errors' => &$this->errors]);
    }

    /**
     * Add error to array.
     *
     * @param string $error Error message
     * @param string $field
     */
    public function addError($error, $field = self::ANY_FIELD)
    {
        if (empty($field)) {
            $field = self::ANY_FIELD;
        }

        if (empty($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $error;
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = ['message' => $this->getMessage(), 'type' => get_class($this), 'field_errors' => []];

        foreach ($this->getErrors() as $field => $messages) {
            foreach ($messages as $message) {
                if (empty($result['field_errors'][$field])) {
                    $result['field_errors'][$field] = [];
                }

                $result['field_errors'][$field][] = $message;
            }
        }

        if ($this->object instanceof DataObject) {
            $result['object_class'] = get_class($this->object);
            $result['object_fields'] = [];

            foreach ($this->object->getFields() as $field) {
                $result['object_fields'][$field] = $this->object->getFieldValue($field);
            }
        }

        return $result;
    }

    /**
     * Return number of errors from specific form.
     *
     * @return array
     */
    public function getErrors()
    {
        return count($this->errors) ? $this->errors : null;
    }

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    /**
     * Return parent object instance.
     *
     * @return DataObject
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set parent object.
     *
     * @param  DataObject           $value
     * @return DataObject
     * @throws InvalidInstanceError
     */
    public function setObject($value)
    {
        if ($value instanceof DataObject || $value === null) {
            $this->object = $value;
        } else {
            throw new InvalidInstanceError('value', $value, 'DataObject');
        }

        return $this->object;
    }

    /**
     * Return field errors.
     *
     * @param  string     $field
     * @return array|null
     */
    public function getFieldErrors($field)
    {
        return isset($this->errors[$field]) ? $this->errors[$field] : null;
    }

    /**
     * Check if a specific field has reported errors.
     *
     * @param  string $field
     * @return bool
     */
    public function hasError($field)
    {
        return isset($this->errors[$field]) && count($this->errors[$field]);
    }

    /**
     * @param string $field
     */
    public function fieldValueIsRequired($field)
    {
        $this->addError(lang('Value of :field field is required', ['field' => $field]), $field);
    }

    /**
     * @param string     $field
     * @param array|null $key_fields
     */
    public function fieldValueNeedsToBeUnique($field, $key_fields = null)
    {
        if ($key_fields && is_foreachable($key_fields)) {
            $this->addError(lang('Combined value of :key_fields fields needs to be unique', ['key_fields' => implode(', ', $key_fields)]), $field);
        } else {
            $this->addError(lang('Value of :field field needs to be unique', ['field' => $field]), $field);
        }
    }

    /**
     * Returns error messages as string.
     *
     * @return string
     */
    public function getErrorsAsString()
    {
        if ($this->hasErrors()) {
            $this_errors = [];
            $errors = $this->getErrors();

            foreach ($errors as $error) {
                $this_errors[] = implode(', ', $error);
            }

            return trim(implode(', ', $this_errors));
        } else {
            return '--';
        }
    }

    /**
     * Returns true if there are error messages reported.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return (bool) count($this->errors);
    }
}
