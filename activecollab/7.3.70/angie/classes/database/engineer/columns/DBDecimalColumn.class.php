<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DBDecimalColumn extends DBNumericColumn
{
    private int $scale;

    /**
     * Construct decimal column.
     *
     * @param mixed $default
     */
    public function __construct(
        string $name,
        int $lenght = 12,
        int $scale = 2,
        $default = null
    )
    {
        if ($default !== null) {
            $default = (float) $default;
        }

        parent::__construct($name, $lenght, $default);

        $this->scale = (int) $scale;
    }

    /**
     * Create and return decimal column.
     *
     * @param  string          $name
     * @param  int             $lenght
     * @param  int             $scale
     * @param  mixed           $default
     * @return DBDecimalColumn
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
        $result = 'decimal(' . $this->length . ', ' . $this->scale . ')';
        if ($this->unsigned) {
            $result .= ' unsigned';
        }

        return $result;
    }

    public function prepareModelDefinition(): string
    {
        $default = $this->getDefault() === null ? '' : ', ' . var_export($this->getDefault(), true);

        $result = "DBDecimalColumn::create('" . $this->getName() . "', " . $this->getLength() . ', ' . $this->getScale() . "$default)";

        if ($this->unsigned) {
            $result .= '->setUnsigned(true)';
        }

        return $result;
    }

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
     * @param  int             $value
     * @return DBDecimalColumn
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
