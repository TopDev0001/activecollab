<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class TeamTimelineFilter extends DataFilter
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
        $this->filter->setIncludeTrackingData(true);
        $this->filter->setCompletedOnFilter(AssignmentFilter::DATE_FILTER_IS_NOT_SET);
        $this->filter->setProjectFilter(Projects::PROJECT_FILTER_ACTIVE);
        $this->filter->setGroupBy(
            AssignmentFilter::GROUP_BY_ASSIGNEE,
            AssignmentFilter::GROUP_BY_PROJECT
        );

        if ($result = $this->filter->run($user, $additional)) {
            return $this->prepareResults($result);
        }

        return null;
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

    /**
     * Remove unused element from array if is settled.
     *
     * @return Dataobject[]|DBResult
     */
    private function prepareResults(array $results)
    {
        foreach (array_keys($results) as $key) {
            if (isset($results[$key]['assignments']['unknow-project'])) {
                unset($results[$key]['assignments']['unknow-project']);
            }
        }

        return $results;
    }
}
