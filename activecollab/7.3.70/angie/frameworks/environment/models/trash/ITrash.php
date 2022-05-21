<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Trash interface.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
interface ITrash
{
    /**
     * Move to trash.
     *
     * @param bool $bulk
     */
    public function moveToTrash(User $by = null, $bulk = false);

    /**
     * Restore from trash.
     *
     * @param bool $bulk
     */
    public function restoreFromTrash($bulk = false);

    public function canTrash(User $user): bool;

    /**
     * Return true if $user can restore this object from trash.
     *
     * @return bool
     */
    public function canRestoreFromTrash(User $user);

    /**
     * Return value of is_trashed field.
     *
     * @return bool
     */
    public function getIsTrashed();

    /**
     * Set value of is_trashed field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsTrashed($value);

    /**
     * Return value of trashed_on field.
     *
     * @return DateTimeValue
     */
    public function getTrashedOn();

    /**
     * Set value of trashed_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setTrashedOn($value);
}
