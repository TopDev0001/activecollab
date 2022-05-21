<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Project element interface.
 *
 * @package activeCollab.modules.system
 * @subpackage models
 */
interface IProjectElement
{
    /**
     * Return project instance.
     *
     * @return Project
     */
    public function &getProject();

    /**
     * Set parent project.
     *
     * @return Project
     */
    public function setProject(Project $project);

    /**
     * Return project ID.
     *
     * @return int
     */
    public function getProjectId();

    /**
     * Set value of project_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setProjectId($value);

    /**
     * Return value of is_hidden_from_clients field.
     *
     * @return bool
     */
    public function getIsHiddenFromClients();

    /**
     * Set value of is_hidden_from_clients field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsHiddenFromClients($value);

    /**
     * Return true if $user can move this element to $target_project.
     *
     * @return bool
     */
    public function canMoveToProject(User $user, Project $target_project);

    /**
     * Return true if $user can create a copy of this element in $target_project.
     *
     * @return bool
     */
    public function canCopyToProject(User $user, Project $target_project);

    /**
     * Move to project.
     */
    public function moveToProject(
        Project $project,
        User $by,
        callable $before_save = null,
        callable $after_save = null
    );

    public function copyToProject(
        Project $project,
        User $by,
        callable $before_save = null,
        callable $after_save = null
    ): IProjectElement;

    /**
     * Get global reference.
     *
     * @return string
     */
    public function getGlobalReference();
}
