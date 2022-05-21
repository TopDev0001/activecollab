<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectsTimelineFilter extends DataFilter
{
    /**
     * @var AssignmentFilter
     */
    public $filter;

    protected function __configure(): void
    {
        parent::__configure();

        $this->filter = new AssignmentFilter();
    }

    public function getExportColumns(): array
    {
        return [];
    }

    public function exportWriteLines(User $user, array $result): void
    {
    }

    public function run(User $user, array $additional = null): ?array
    {
        $this->filter->setExtendTaskListNameWhenGrouping(false);
        $this->filter->setCompletedOnFilter(AssignmentFilter::DATE_FILTER_IS_NOT_SET);
        $this->filter->setGroupBy(AssignmentFilter::GROUP_BY_PROJECT, AssignmentFilter::GROUP_BY_TASK_LIST);

        return $this->filter->run($user, $additional);
    }

    public function canRun(User $user): bool
    {
        return $user->isPowerUser();
    }

    /**
     * Set non-field value during DataManager::create() and DataManager::update() calls.
     *
     * @param string $attribute
     * @param mixed  $value
     */
    public function setAttribute($attribute, $value)
    {
        $this->filter->setAttribute($attribute, $value);
    }
}
