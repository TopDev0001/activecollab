<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class DBNumericColumn extends DBColumn
{
    protected bool $has_size = true;

    /**
     * Field length.
     *
     * @var int
     */
    protected $length;
    protected bool $unsigned = false;

    /**
     * Construct numeric column.
     *
     * @param string|int $lenght
     * @param mixed      $default
     */
    public function __construct(string $name, $lenght = DBColumn::NORMAL, $default = null)
    {
        parent::__construct($name, $default);

        $this->length = (int) $lenght;
    }

    public function loadFromRow(array $row): void
    {
        parent::loadFromRow($row);

        $this->unsigned = strpos($row['Type'], 'unsigned') !== false;
    }

    public function processAdditional(array $additional): void
    {
        parent::processAdditional($additional);

        if (!empty($additional[0])) {
            $this->length = (int) $additional[0];
        }
    }

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
     * Return length.
     *
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set field lenght.
     *
     * @param  int             $value
     * @return DBNumericColumn
     */
    public function &setLenght($value)
    {
        $this->length = (int) $value;

        return $this;
    }

    public function getUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function &setUnsigned(bool $value): DBNumericColumn
    {
        $this->unsigned = (bool) $value;

        return $this;
    }
}
