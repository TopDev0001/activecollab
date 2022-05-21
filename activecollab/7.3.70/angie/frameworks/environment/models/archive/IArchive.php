<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Archive interface.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
interface IArchive
{
    /**
     * Return true if parent object is archived.
     *
     * @return bool
     */
    public function getIsArchived();

    /**
     * Move to archive.
     *
     * @param bool $bulk
     * @return
     */
    public function moveToArchive(User $by, $bulk = false);

    /**
     * Restore from archive.
     *
     * @param bool $bulk
     */
    public function restoreFromArchive($bulk = false);

    public function canArchive(User $user): bool;
}
