<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DBNameColumn extends DBStringColumn
{
    private bool $unique;

    /**
     * Additional fields that are used to validate uniqueness of the name.
     *
     * @var array
     */
    private $unique_context = null;

    /**
     * Construct name column instance.
     *
     * @param int|string $length
     * @param array      $unique_context
     */
    public function __construct(int $length = self::MAX_LENGTH, bool $unique = false, $unique_context = null)
    {
        parent::__construct('name', $length, '');

        $this->unique = (bool) $unique;

        if ($unique_context) {
            $this->unique_context = (array) $unique_context;
        }
    }

    public function addedToTable(): void
    {
        if ($this->unique) {
            $context = ['name'];

            if (is_array($this->unique_context)) {
                $context = array_merge($context, $this->unique_context);
            }

            $this->table->addIndex(new DBIndex('name', DBIndex::UNIQUE, $context));
        }

        parent::addedToTable();
    }
}
