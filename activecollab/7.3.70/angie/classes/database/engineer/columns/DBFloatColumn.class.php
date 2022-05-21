<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Class that represents FLOAT/DOUBLE database columns.
 *
 * @package angie.library.database
 * @subpackage engineer
 */
class DBFloatColumn extends DBNumericColumn
{
    /**
     * Column scale.
     *
     * @var int
     */
    private $scale = 2;

    /**
     * Construct float column.
     *
     * @param string $name
     * @param int    $lenght
     * @param int    $scale
     * @param mixed  $default
     */
    public function __construct($name, $lenght = 12, $scale = 2, $default = null)
    {
        if ($default !== null) {
            $default = (float) $default;
        }

        parent::__construct($name, $lenght, $default);

        $this->scale = (int) $scale;
    }

    /**
     * Create and return float column.
     *
     * @param  string        $name
     * @param  int           $lenght
     * @param  int           $scale
     * @param  mixed         $default
     * @return DBFloatColumn
     */
    public static function create($name, $lenght = 12, $scale = 2, $default = null)
    {
        return new self($name, $lenght, $scale, $default);
    }

    public function processAdditional(array $additional): void
    {
        parent::processAdditional($additional);

        if (!empty($additional[1])) {
            $this->scale = (int) $additional[1];
        }
    }

    public function prepareTypeDefinition(): string
    {
        $result = 'float(' . $this->length . ', ' . $this->scale . ')';
        if ($this->unsigned) {
            $result .= ' unsigned';
        }

        return $result;
    }

    public function prepareDefault(): string
    {
        return parent::prepareDefault() === null
            ? ''
            : (float) parent::prepareDefault();
    }

    public function prepareModelDefinition(): string
    {
        $default = $this->getDefault() === null ? '' : ', ' . var_export($this->getDefault(), true);

        $result = "DBFloatColumn::create('" . $this->getName() . "', " . $this->getLength() . ', ' . $this->getScale() . "$default)";

        if ($this->unsigned) {
            $result .= '->setUnsigned(true)';
        }

        return $result;
    }

    // ---------------------------------------------------
    //  Model generator
    // ---------------------------------------------------

    /**
     * Return scale.
     *
     * @return int
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * Set scale.
     *
     * @param  int           $value
     * @return DBFloatColumn
     */
    public function &setScale($value)
    {
        $this->scale = (int) $value;

        return $this;
    }

    public function getPhpType(): string
    {
        return 'float';
    }

    public function getCastingCode(): string
    {
        return '(float) $value';
    }
}
