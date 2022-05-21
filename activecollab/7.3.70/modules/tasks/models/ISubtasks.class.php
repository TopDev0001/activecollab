<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Subtasks interface definition.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage models
 */
interface ISubtasks
{
    /**
     * Return a list of all subtasks.
     *
     * @return DBResult|Subtask[]
     */
    public function getSubtasks(bool $include_trashed = false): ?iterable;

    public function setSubtasks(?iterable $recurring_subtasks): ?iterable;
}
