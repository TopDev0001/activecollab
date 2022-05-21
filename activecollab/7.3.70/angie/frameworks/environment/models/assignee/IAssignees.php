<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Assignees interface definition.
 *
 * @package angie.frameworks.assignees
 * @subpackage models
 */
interface IAssignees
{
    /**
     * Returns true if $user is assigned to this object.
     *
     * @return bool
     */
    public function isAssignee(User $user);

    /**
     * Returns true if this object has assignee set.
     *
     * @return bool
     */
    public function hasAssignee();

    /**
     * Return assignee instance.
     *
     * @return User|null
     */
    public function getAssignee();

    /**
     * Set assignee.
     *
     * @param User|null $assignee
     * @param mixed     $delegated_by
     * @param bool      $save
     */
    public function setAssignee($assignee, $delegated_by = null, $save = true);

    /**
     * Return user who delegated this assignment to assignees.
     *
     * @return User
     */
    public function getDelegatedBy();

    /**
     * Set user who delegated this instance.
     *
     * @param  User $user
     * @return User
     */
    public function setDelegatedBy($user);

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return value of assignee_id field.
     *
     * @return int
     */
    public function getAssigneeId();

    /**
     * Set value of assignee_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setAssigneeId($value);

    /**
     * Return value of delegated_by_id field.
     *
     * @return int
     */
    public function getDelegatedById();

    /**
     * Set value of delegated_by_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setDelegatedById($value);
}
