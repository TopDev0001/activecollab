<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DBFkColumn extends DBIntegerColumn
{
    private bool $add_key;

    /**
     * Create a new instance.
     *
     * @param int $default
     */
    public function __construct(string $name, $default = 0, bool $add_key = false)
    {
        if (!is_int($default) || $default < 0) {
            throw new InvalidParamError('default', $default, 'Default value must be an unsigned integer value');
        }

        $this->add_key = $add_key;

        parent::__construct($name, DBColumn::NORMAL, $default);

        $this->setUnsigned(true);
    }

    /**
     * Create new integer column instance.
     *
     * @param  string     $name
     * @param  mixed      $default
     * @param  bool       $add_key = false
     * @return DBFkColumn
     */
    public static function create($name, $default = 0, $add_key = false)
    {
        return new self($name, $default);
    }

    public function addedToTable(): void
    {
        if ($this->add_key) {
            $this->table->addIndex(new DBIndex($this->name));
        }

        parent::addedToTable();
    }
}
