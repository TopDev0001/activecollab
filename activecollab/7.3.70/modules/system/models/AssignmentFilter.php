<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use Angie\Globalization;

class AssignmentFilter extends DataFilter
{
    // User filter
    public const USER_FILTER_NOT_ASSIGNED = 'not_assigned';
    public const USER_FILTER_LOGGED_USER_RESPONSIBLE = 'logged_user_responsible';
    public const USER_FILTER_COMPANY_MEMBER_RESPONSIBLE = 'company_responsible';
    public const USER_FILTER_SELECTED_RESPONSIBLE = 'selected_responsible';

    // Label filter
    public const LABEL_FILTER_ANY = 'any';
    public const LABEL_FILTER_IS_NOT_SET = 'is_not_set';
    public const LABEL_FILTER_SELECTED = 'selected';
    public const LABEL_FILTER_NOT_SELECTED = 'not_selected';

    // Task list filter
    public const TASK_LIST_FILTER_ANY = 'any';
    public const TASK_LIST_FILTER_IS_NOT_SET = 'is_not_set';
    public const TASK_LIST_FILTER_SELECTED = 'selected';

    // Job type filter
    public const JOB_TYPE_FILTER_ANY = 'any';
    public const JOB_TYPE_FILTER_IS_SET = 'is_set';
    public const JOB_TYPE_FILTER_IS_NOT_SET = 'is_not_set';
    public const JOB_TYPE_FILTER_SELECTED = 'selected';

    // Group
    public const GROUP_BY_ASSIGNEE = 'assignee';
    public const GROUP_BY_PROJECT = 'project';
    public const GROUP_BY_PROJECT_CLIENT = 'project_client';
    public const GROUP_BY_LABEL = 'label';
    public const GROUP_BY_TASK_LIST = 'task_list';
    public const GROUP_BY_CREATED_ON = 'created_on';
    public const GROUP_BY_DUE_ON = 'due_on';
    public const GROUP_BY_COMPLETED_ON = 'completed_on';
    /**
     * @var string
     */
    private $task_url_pattern;
    private $subtask_url_pattern;

    public function run(User $user, array $additional = null): ?array
    {
        $exclude = $additional && isset($additional['exclude']) ? $additional['exclude'] : null;

        // Get projects that we can query based on given criteria
        $project_ids = Projects::getProjectIdsByDataFilter($this, $user);

        // Query subtasks based on given criteria (optional, checked internally)
        [
            $include_subtasks,
            $subtasks,
            $subtask_parent_ids,
        ] = $this->querySubtasks($user, $project_ids, $exclude);

        // Query tasks (extended with subtask parents, if needed)
        [
            $assignments,
            $projects,
            $categories,
            $task_lists,
            $companies,
        ] = $this->queryTasks($user, $project_ids, $exclude, $subtask_parent_ids);

        $task_ids = [];

        if ($assignments) {
            foreach ($assignments as $assignment) {
                $task_ids[] = $assignment['id'];
            }
        }

        // Query labels data
        $labels_data = $this->queryLabelsData($task_ids);

        // Query tracking data
        [
            $include_tracking_data,
            $tracking_data,
        ] = $this->queryTrackingData($user, $task_ids);

        if ($assignments instanceof DBResult) {
            $group_by = $this->getGroupBy();

            $result = $this->groupAssignmentsInFirstWave(array_shift($group_by), $user, $assignments, $projects, $labels_data, $subtasks); // Group, first wave

            // Now add subtasks and prepare individual rows with additional data, type cast etc
            foreach (array_keys($result) as $k) {
                if (count($result[$k]['assignments'])) {
                    foreach ($result[$k]['assignments'] as $assignment_id => $assignment) {
                        $result[$k]['assignments'][$assignment_id]['project'] = $projects && isset($projects[$result[$k]['assignments'][$assignment_id]['project_id']]) ? $projects[$result[$k]['assignments'][$assignment_id]['project_id']] : null;
                        $result[$k]['assignments'][$assignment_id]['task_list'] = $task_lists && isset($task_lists[$result[$k]['assignments'][$assignment_id]['task_list_id']]) ? $task_lists[$result[$k]['assignments'][$assignment_id]['task_list_id']] : null;

                        $result[$k]['assignments'][$assignment_id]['client_id'] = $companies && isset($companies[$result[$k]['assignments'][$assignment_id]['project_id']]) ? $companies[$result[$k]['assignments'][$assignment_id]['project_id']]['client_id'] : null;
                        $result[$k]['assignments'][$assignment_id]['client_name'] = $companies && isset($companies[$result[$k]['assignments'][$assignment_id]['project_id']]) ? $companies[$result[$k]['assignments'][$assignment_id]['project_id']]['client_name'] : null;

                        $result[$k]['assignments'][$assignment_id]['assignee'] = Users::getUserDisplayNameById($result[$k]['assignments'][$assignment_id]['assignee_id'], false);
                        $result[$k]['assignments'][$assignment_id]['created_by'] = $this->getUserDisplayName($result[$k]['assignments'][$assignment_id]['created_by_id'], [
                            'full_name' => $assignment['created_by_name'],
                            'email' => $assignment['created_by_email'],
                        ]);
                        $result[$k]['assignments'][$assignment_id]['completed_by'] = $this->getUserDisplayName($result[$k]['assignments'][$assignment_id]['completed_by_id'], [
                            'full_name' => $assignment['completed_by_name'],
                            'email' => $assignment['completed_by_email'],
                        ]);

                        $result[$k]['assignments'][$assignment_id]['labels'] = empty($labels_data[$assignment_id]) ? [] : $labels_data[$assignment_id];

                        if ($include_subtasks && $subtasks && isset($subtasks[$assignment_id])) {
                            foreach (array_keys($subtasks[$assignment_id]) as $subtask_id) {
                                $subtasks[$assignment_id][$subtask_id]['permalink'] = $this->getSubtaskPermalink($result[$k]['assignments'][$assignment_id]['project_id'], $result[$k]['assignments'][$assignment_id]['id'], $subtask_id);
                            }

                            $result[$k]['assignments'][$assignment_id]['subtasks'] = $subtasks[$assignment_id];
                        }

                        $result[$k]['assignments'][$assignment_id]['permalink'] = $this->getTaskPermalink($assignment);

                        if ($include_tracking_data) {
                            if ($tracking_data && isset($tracking_data[$assignment_id])) {
                                $result[$k]['assignments'][$assignment_id]['tracked_time'] = isset($tracking_data[$assignment_id]['tracked_time']) ? $tracking_data[$assignment_id]['tracked_time'] : null;
                            } else {
                                $result[$k]['assignments'][$assignment_id]['tracked_time'] = null;
                            }
                        }

                        // Calculate age
                        if (isset($result[$k]['assignments'][$assignment_id]['age'])) {
                            $created_on = new DateTime($result[$k]['assignments'][$assignment_id]['created_on']->advance(Globalization::getUserGmtOffset(), false)->format('Y-m-d'));
                            $now = new DateTime(DateTimeValue::now()->advance(Globalization::getUserGmtOffset(), false)->format('Y-m-d'));
                            $result[$k]['assignments'][$assignment_id]['age'] = $now->diff($created_on)->days;
                        }
                    }
                } else {
                    unset($result[$k]);
                }
            }

            if (count($group_by) > 0) {
                $this->groupAssignmentsInSecondWave(array_shift($group_by), $user, $result, $projects, $subtasks);
            }

            return $result;
        }

        return null;
    }

    /**
     * Query subtasks.
     *
     * @param  User  $user
     * @param  array $project_ids
     * @param  array $exclude
     * @return array
     */
    private function querySubtasks($user, $project_ids, $exclude)
    {
        $subtasks = null;
        $include_subtasks = false;

        if ($user instanceof Client) {
            $type_filter = DB::prepare('(tasks.project_id IN (?) AND subtasks.completed_on IS NULL AND is_hidden_from_clients = ?)', $project_ids, false);
        } else {
            $type_filter = DB::prepare('(tasks.project_id IN (?) AND subtasks.completed_on IS NULL)', $project_ids);
        }

        if ($this->getIncludeSubtasks() && $this->canMatchAnySubtask()) {
            $include_subtasks = true;

            $conditions = $this->prepareConditions($user, 'subtasks');
            $exclude_conditions = $this->prepareExcludeConditions($exclude, 'subtasks', 'parent_type', 'parent_id');

            if ($exclude_conditions) {
                $conditions = "($conditions AND $exclude_conditions)";
            }

            $order_by = $this->getSubtasksOrderBy();

            if ($subtask_rows = DB::execute("SELECT subtasks.id, 'Subtask' AS 'type', subtasks.task_id, subtasks.assignee_id, subtasks.body, subtasks.created_on, DATEDIFF(UTC_TIMESTAMP(), subtasks.created_on) AS 'age', subtasks.created_by_id, subtasks.created_by_name, subtasks.created_by_email, subtasks.due_on, subtasks.completed_on, subtasks.completed_by_id, subtasks.completed_by_name, subtasks.completed_by_email FROM subtasks LEFT JOIN tasks ON tasks.id = subtasks.task_id WHERE ($type_filter) AND ($conditions) ORDER BY $order_by")) {
                $subtask_rows->setCasting([
                    'created_on' => DBResult::CAST_DATETIME,
                    'age' => DBResult::CAST_INT,
                    'due_on' => DBResult::CAST_DATE,
                    'completed_on' => DBResult::CAST_DATETIME,
                ]);

                $subtasks = [];

                foreach ($subtask_rows as $subtask_row) {
                    $subtask_id = (int) $subtask_row['id'];
                    $task_id = $subtask_row['task_id'];

                    if (isset($subtasks[$task_id])) {
                        $subtasks[$task_id][$subtask_id] = $subtask_row;
                    } else {
                        $subtasks[$task_id] = [$subtask_id => $subtask_row];
                    }

                    $subtasks[$task_id][$subtask_id]['assignee'] = Users::getUserDisplayNameById($subtasks[$task_id][$subtask_id]['assignee_id'], false);

                    $subtasks[$task_id][$subtask_id]['created_by'] = $this->getUserDisplayName($subtasks[$task_id][$subtask_id]['created_by_id'], [
                        'full_name' => $subtask_row['created_by_name'],
                        'email' => $subtask_row['created_by_email'],
                    ]);

                    $subtasks[$task_id][$subtask_id]['completed_by'] = $this->getUserDisplayName($subtasks[$task_id][$subtask_id]['completed_by_id'], [
                        'full_name' => $subtask_row['completed_by_name'],
                        'email' => $subtask_row['completed_by_email'],
                    ]);
                }
            }
        }

        return [$include_subtasks, $subtasks, ($subtasks ? array_keys($subtasks) : null)];
    }

    /**
     * Returns true if this filter also matches subtasks.
     *
     * @return bool
     */
    public function getIncludeSubtasks()
    {
        return $this->getAdditionalProperty('include_subtasks', true);
    }

    /**
     * Return true if filter is set up in such a way that any subtask can be queried and matched.
     *
     * @return bool
     */
    public function canMatchAnySubtask()
    {
        return $this->getLabelFilter() === self::LABEL_FILTER_ANY && $this->getDueOnFilter() === self::DATE_FILTER_ANY && $this->getTaskListFilter() === self::TASK_LIST_FILTER_ANY;
    }

    /**
     * Return label filter.
     *
     * @return string
     */
    public function getLabelFilter()
    {
        return $this->getAdditionalProperty('label_filter', self::LABEL_FILTER_ANY);
    }

    /**
     * Return due date filter value.
     *
     * @return string
     */
    public function getDueOnFilter()
    {
        return $this->getAdditionalProperty('due_on_filter', self::DATE_FILTER_ANY);
    }

    /**
     * Return task list filter.
     *
     * @return string
     */
    public function getTaskListFilter()
    {
        return $this->getAdditionalProperty('task_list_filter', self::TASK_LIST_FILTER_ANY);
    }

    /**
     * Prepare conditions based on filter settings.
     *
     * @param  string $table_name
     * @return string
     */
    private function prepareConditions(User $user, $table_name)
    {
        $conditions = [DB::prepare("($table_name.is_trashed = ?)", false)];

        // User filter
        switch ($this->getUserFilter()) {
            case self::USER_FILTER_ANYBODY:
                break;

            // Not assigned to anyone
            case self::USER_FILTER_NOT_ASSIGNED:
                $conditions[] = "($table_name.assignee_id IS NULL OR $table_name.assignee_id = '0')";

                break;

            // Logged user, applicable only to project objects
            case self::USER_FILTER_LOGGED_USER:
                $conditions[] = DB::prepare("($table_name.assignee_id = ?)", $user->getId());

                break;

            // Logged user is responsible
            case self::USER_FILTER_LOGGED_USER_RESPONSIBLE:
                $conditions[] = DB::prepare("($table_name.assignee_id = ?)", $user->getId());

                break;

            // All members of a specific company, responsible or assigned
            case self::USER_FILTER_COMPANY_MEMBER:
                $company_id = $this->getUserFilterCompanyId();

                if ($company_id) {
                    $company = Companies::findById($company_id);

                    if ($company instanceof Company) {
                        $visible_user_ids = $user->getVisibleCompanyUserIds($company);

                        if ($visible_user_ids) {
                            $conditions[] = DB::prepare("($table_name.assignee_id IN (?))", $visible_user_ids);
                        } else {
                            throw new DataFilterConditionsError('user_filter', self::USER_FILTER_COMPANY_MEMBER, $company_id, "User can't see any members of target company");
                        }
                    } else {
                        throw new DataFilterConditionsError('user_filter', self::USER_FILTER_COMPANY_MEMBER, $company_id, 'Company not found');
                    }
                } else {
                    throw new DataFilterConditionsError('user_filter', self::USER_FILTER_COMPANY_MEMBER, $company_id, 'Company not selected');
                }

                break;

            // All members of a specific company, responsible only
            case self::USER_FILTER_COMPANY_MEMBER_RESPONSIBLE:
                $company_id = $this->getUserFilterCompanyId();

                if ($company_id) {
                    $company = Companies::findById($company_id);

                    if ($company instanceof Company) {
                        $visible_user_ids = $user->getVisibleCompanyUserIds($company);

                        if ($visible_user_ids) {
                            $conditions[] = DB::prepare("($table_name.assignee_id IN (?))", $visible_user_ids);
                        } else {
                            throw new DataFilterConditionsError('user_filter', self::USER_FILTER_COMPANY_MEMBER_RESPONSIBLE, $company_id, "User can't see any members of target company");
                        }
                    } else {
                        throw new DataFilterConditionsError('user_filter', self::USER_FILTER_COMPANY_MEMBER_RESPONSIBLE, $company_id, 'Company not found');
                    }
                } else {
                    throw new DataFilterConditionsError('user_filter', self::USER_FILTER_COMPANY_MEMBER_RESPONSIBLE, $company_id, 'Company not selected');
                }

                break;

            // Selected users, responslbe or assigned
            case self::USER_FILTER_SELECTED:
                $user_ids = $this->getUserFilterSelectedUsers();

                if ($user_ids) {
                    $visible_user_ids = $user->getVisibleUserIds();

                    if ($visible_user_ids) {
                        foreach ($user_ids as $k => $v) {
                            if (!in_array($v, $visible_user_ids)) {
                                unset($user_ids[$k]);
                            }
                        }

                        if (count($user_ids)) {
                            $conditions[] = DB::prepare("($table_name.assignee_id IN (?))", $user_ids);
                        } else {
                            throw new DataFilterConditionsError('user_filter', self::USER_FILTER_SELECTED, $user_ids, 'Non of the selected users is visible');
                        }
                    } else {
                        throw new DataFilterConditionsError('user_filter', self::USER_FILTER_SELECTED, $user_ids, "User can't see anyone else");
                    }
                } else {
                    throw new DataFilterConditionsError('user_filter', self::USER_FILTER_SELECTED, $user_ids, 'No users selected');
                }

                break;

            // Selected users, responslbe only
            case self::USER_FILTER_SELECTED_RESPONSIBLE:
                $user_ids = $this->getUserFilterSelectedUsers();

                if ($user_ids) {
                    $visible_user_ids = $user->getVisibleUserIds();

                    if ($visible_user_ids) {
                        foreach ($user_ids as $k => $v) {
                            if (!in_array($v, $visible_user_ids)) {
                                unset($user_ids[$k]);
                            }
                        }

                        if (count($user_ids)) {
                            $conditions[] = DB::prepare("($table_name.assignee_id IN (?))", $user_ids);
                        } else {
                            throw new DataFilterConditionsError('user_filter', self::USER_FILTER_SELECTED_RESPONSIBLE, $user_ids, 'Non of the selected users is visible');
                        }
                    } else {
                        throw new DataFilterConditionsError('user_filter', self::USER_FILTER_SELECTED_RESPONSIBLE, $user_ids, "User can't see anyone else");
                    }
                } else {
                    throw new DataFilterConditionsError('user_filter', self::USER_FILTER_SELECTED_RESPONSIBLE, $user_ids, 'No users selected');
                }

                break;
            default:
                throw new DataFilterConditionsError('user_filter', $this->getUserFilter(), 'mixed', 'Unknown user filter');
        }

        // Task list and label related filters apply only to project objects
        if ($table_name == 'tasks') {
            // Label filter
            switch ($this->getLabelFilter()) {
                case self::LABEL_FILTER_ANY:
                    break;

                case self::LABEL_FILTER_IS_NOT_SET:
                    $conditions[] = DB::prepare("($table_name.id IN (" . Labels::getParentIdsWithNoLabelsSqlQuery('tasks', Task::class) . '))');

                    break;

                case self::LABEL_FILTER_SELECTED:
                    $label_names = $this->getLabelNames();

                    if ($label_names && is_foreachable($label_names)) {
                        $conditions[] = DB::prepare("($table_name.id IN (" . Labels::getParentIdsByLabelsSqlQuery(Task::class, TaskLabel::class, $label_names, false) . '))');
                    } else {
                        throw new DataFilterConditionsError('label_filter', self::LABEL_FILTER_SELECTED, $label_names, 'Invalid label names value');
                    }

                    break;

                case self::LABEL_FILTER_NOT_SELECTED:
                    $label_names = $this->getLabelNames();

                    if ($label_names && is_foreachable($label_names)) {
                        $conditions[] = DB::prepare("($table_name.id NOT IN (" . Labels::getParentIdsByLabelsSqlQuery(Task::class, TaskLabel::class, $label_names, false) . '))');
                    } else {
                        throw new DataFilterConditionsError('label_filter', self::LABEL_FILTER_SELECTED, $label_names, 'Invalid label names value');
                    }

                    break;

                default:
                    throw new DataFilterConditionsError('label_filter', $this->getLabelFilter(), 'mixed', 'Unknown label filter');
            }

            // Task list filter
            switch ($this->getTaskListFilter()) {
                case self::TASK_LIST_FILTER_ANY:
                    break;

                case self::TASK_LIST_FILTER_IS_NOT_SET:
                    $conditions[] = DB::prepare("($table_name.task_list_id = ?)", 0);

                    break;

                case self::TASK_LIST_FILTER_SELECTED:
                    $task_list_names = $this->getTaskListNames();

                    $task_list_ids = $task_list_names ? TaskLists::getIdsByNames($task_list_names) : null;

                    if ($task_list_ids) {
                        $conditions[] = DB::prepare("($table_name.task_list_id IN (?))", $task_list_ids);
                    } else {
                        throw new DataFilterConditionsError('task_list_filter', self::TASK_LIST_FILTER_SELECTED, $task_list_names, 'There are no task lists found by the names provided');
                    }

                    break;

                default:
                    throw new DataFilterConditionsError('task_list_filter', $this->getTaskListFilter(), 'mixed', 'Unknown task list filter');
            }

            $this->prepareJobTypeFilterConditions($conditions);
        }

        $this->prepareDateFilterConditions($user, 'created', $table_name, $conditions);
        if ($table_name = 'tasks') {
            $this->prepareDateFilterConditions($user, 'start', $table_name, $conditions);
        }
        $this->prepareDateFilterConditions($user, 'due', $table_name, $conditions);
        $this->prepareDateFilterConditions($user, 'completed', $table_name, $conditions);

        $this->prepareUserFilterConditions($user, 'created', $table_name, $conditions);
        $this->prepareUserFilterConditions($user, 'completed', $table_name, $conditions);
        $this->prepareUserFilterConditions($user, 'delegated', $table_name, $conditions);

        return implode(' AND ', $conditions);
    }

    /**
     * Return user filter value.
     *
     * @return string
     */
    public function getUserFilter()
    {
        return $this->getAdditionalProperty('user_filter', self::USER_FILTER_ANYBODY);
    }

    /**
     * Return company ID set for user filter.
     *
     * @return int
     */
    public function getUserFilterCompanyId()
    {
        return $this->getAdditionalProperty('company_id');
    }

    /**
     * Return array of selected users.
     *
     * @return array
     */
    public function getUserFilterSelectedUsers()
    {
        return $this->getAdditionalProperty('selected_users');
    }

    /**
     * Return label names.
     *
     * @return string
     */
    public function getLabelNames()
    {
        return $this->getAdditionalProperty('label_names');
    }

    /**
     * Return label names.
     *
     * @return string
     */
    public function getTaskListNames()
    {
        return $this->getAdditionalProperty('task_list_names');
    }

    /**
     * Prepare job type filter conditions.
     */
    private function prepareJobTypeFilterConditions(array &$conditions)
    {
        if ($this->getJobTypeFilter() == self::JOB_TYPE_FILTER_IS_SET) {
            $conditions[] = "(tasks.job_type_id > '0')";
        } else {
            if ($this->getJobTypeFilter() == self::JOB_TYPE_FILTER_IS_NOT_SET) {
                $conditions[] = "(tasks.job_type_id = '0')";
            } else {
                if ($this->getJobTypeFilter() == self::JOB_TYPE_FILTER_SELECTED) {
                    $job_type_ids = $this->getJobTypeIds();

                    if (empty($job_type_ids)) {
                        throw new DataFilterConditionsError('job_type_filter', $this->getJobTypeFilter(), $job_type_ids, 'Invalid job type IDs value');
                    } else {
                        $conditions[] = DB::prepare('(tasks.job_type_id IN (?))', $job_type_ids);
                    }
                } else {
                    if ($this->getJobTypeFilter() != self::JOB_TYPE_FILTER_ANY) {
                        throw new DataFilterConditionsError('job_type_filter', $this->getJobTypeFilter(), 'mixed', 'Unknown job type filter');
                    }
                }
            }
        }
    }

    /**
     * Return job type filter.
     *
     * @return string
     */
    public function getJobTypeFilter()
    {
        return $this->getAdditionalProperty('job_type_filter', self::JOB_TYPE_FILTER_ANY);
    }

    /**
     * Return job type ID-s that we need to filter by.
     *
     * @return array|null
     */
    public function getJobTypeIds()
    {
        return $this->getAdditionalProperty('job_type_ids');
    }

    // ---------------------------------------------------
    //  Group methods
    // ---------------------------------------------------

    /**
     * Return exclude conditions.
     *
     * @param  array  $exclude
     * @param  string $table_name
     * @param  string $type_field_name
     * @param  string $id_field_name
     * @return string
     */
    public function prepareExcludeConditions($exclude, $table_name, $type_field_name = 'type', $id_field_name = 'id')
    {
        if (is_foreachable($exclude)) {
            $result = [];

            foreach ($exclude as $type => $ids) {
                if ($type && $ids) {
                    $result[] = DB::prepare("($table_name.$type_field_name = ? AND $table_name.$id_field_name IN (?))", $type, $ids);
                }
            }

            return count($result) ? 'NOT (' . implode(' AND ', $result) . ')' : '';
        } else {
            return '';
        }
    }

    /**
     * Return how subtasks should be ordered (based on group by settings).
     *
     * @return string
     */
    private function getSubtasksOrderBy()
    {
        $group_by = $this->getGroupBy();

        switch (array_shift($group_by)) {
            case self::GROUP_BY_CREATED_ON:
                return 'subtasks.created_on';
            case self::GROUP_BY_DUE_ON:
                return 'ISNULL(subtasks.due_on), subtasks.due_on';
            case self::GROUP_BY_COMPLETED_ON:
                return 'ISNULL(subtasks.completed_on), subtasks.completed_on';
            default:
                return 'subtasks.completed_on';
        }
    }

    /**
     * Query project objects based on given parameters.
     *
     * @param  User  $user
     * @param  array $project_ids
     * @param  array $exclude
     * @param  mixed $subtask_parent_ids
     * @return array
     */
    private function queryTasks($user, $project_ids, $exclude, $subtask_parent_ids)
    {
        $order_by = $this->getTasksOrderBy();

        if ($user instanceof Client) {
            $type_filter = DB::prepare('(tasks.project_id IN (?) AND tasks.is_hidden_from_clients = ?)', $project_ids, false);
        } else {
            $type_filter = DB::prepare('(tasks.project_id IN (?))', $project_ids);
        }

        if ($type_filter) {
            $conditions = $this->prepareConditions($user, 'tasks');
            $exclude_conditions = $this->prepareExcludeConditions($exclude, 'tasks', 'type', 'id');

            if ($exclude_conditions) {
                $conditions = "($conditions AND $exclude_conditions)";
            }

            $fields = $this->getTaskTableFieldsToQuery();

            if (isset($subtask_parent_ids)) {
                $subtask_parents = DB::prepare('(tasks.id IN (?))', $subtask_parent_ids);

                if ($conditions) {
                    $select_assignments_sql = "SELECT DISTINCT $fields FROM tasks WHERE ((($conditions) AND ($type_filter)) OR ($subtask_parents)) ORDER BY $order_by";
                } else {
                    $select_assignments_sql = "SELECT DISTINCT $fields FROM tasks WHERE $subtask_parents ORDER BY $order_by";
                }
            } else {
                if ($conditions) {
                    $select_assignments_sql = "SELECT DISTINCT $fields FROM tasks WHERE ($conditions) AND ($type_filter) ORDER BY $order_by";
                } else {
                    $select_assignments_sql = null; // No subtasks to extend search, and no records that could match this filter
                }
            }
        } else {
            $select_assignments_sql = null;
        }

        $assignments = $select_assignments_sql ? DB::execute($select_assignments_sql) : null;

        $projects = [];
        $companies = [];
        $task_lists = [];
        $categories = [];

        if ($assignments instanceof DBResult) {
            $assignments->setCasting([
                'created_on' => DBResult::CAST_DATETIME,
                'age' => DBResult::CAST_INT,
                'start_on' => DBResult::CAST_DATE,
                'due_on' => DBResult::CAST_DATE,
                'completed_on' => DBResult::CAST_DATETIME,
            ]);

            foreach ($assignments as $assignment) {
                if ($assignment['project_id'] && !isset($projects[$assignment['project_id']])) {
                    $projects[(int) $assignment['project_id']] = null;
                }

                if ($assignment['task_list_id'] && !isset($projects[$assignment['task_list_id']])) {
                    $task_lists[(int) $assignment['task_list_id']] = null;
                }
            }

            $projects = count($projects) ? Projects::getIdNameMapByIds(array_keys($projects)) : null;
            $task_lists = count($task_lists) ? TaskLists::getIdNameMap(array_keys($task_lists)) : null;
            $companies = count($projects) ? Companies::getIdNameMapByProjectIds(array_keys($projects)) : null;
        }

        return [$assignments, $projects, $categories, $task_lists, $companies];
    }

    /**
     * Return how subtasks should be ordered (based on group by settings).
     *
     * @return string
     */
    private function getTasksOrderBy()
    {
        $group_by = $this->getGroupBy();

        switch (array_shift($group_by)) {
            case self::GROUP_BY_CREATED_ON:
                return 'tasks.created_on, tasks.is_important DESC';
            case self::GROUP_BY_DUE_ON:
                return 'ISNULL(tasks.due_on), tasks.due_on, tasks.is_important DESC, ISNULL(tasks.position), tasks.position';
            case self::GROUP_BY_COMPLETED_ON:
                return 'ISNULL(tasks.completed_on), tasks.completed_on';
            default:
                return 'tasks.is_important DESC, ISNULL(tasks.position), tasks.position';
        }
    }

    /**
     * Return a list of table fields to query.
     *
     * @return string
     */
    private function getTaskTableFieldsToQuery()
    {
        $estimate_fields = $this->getIncludeTrackingData() ? ', tasks.estimate AS estimated_time, tasks.job_type_id AS estimated_job_type_id' : '';

        return "tasks.id, 'Task' AS 'type', tasks.project_id, tasks.assignee_id, tasks.task_list_id, tasks.name, tasks.body, tasks.created_on, DATEDIFF(UTC_TIMESTAMP(), tasks.created_on) AS 'age', tasks.created_by_id, tasks.created_by_name, tasks.created_by_email, tasks.start_on, tasks.due_on, tasks.completed_on, tasks.completed_by_id, tasks.completed_by_name, tasks.completed_by_email, tasks.is_important, tasks.task_number, tasks.is_hidden_from_clients, tasks.position $estimate_fields";
    }

    /**
     * Returns true if this filter also needs to return tracking data.
     *
     * @return bool
     */
    public function getIncludeTrackingData()
    {
        return $this->getAdditionalProperty('include_tracking_data', false);
    }

    /**
     * Query tracked time and estimates.
     *
     * @param  array $task_ids
     * @return array
     */
    public function queryLabelsData($task_ids)
    {
        $labels_data = [];

        if (is_foreachable($task_ids)) {
            if ($rows = DB::execute("SELECT pl.parent_id AS 'task_id', l.id AS 'label_id', l.name AS 'label_name' FROM parents_labels AS pl LEFT JOIN labels AS l ON l.id = pl.label_id WHERE pl.parent_type = 'Task' AND pl.parent_id IN (?) ORDER BY label_name", $task_ids)) {
                foreach ($rows as $row) {
                    if (empty($labels_data[$row['task_id']])) {
                        $labels_data[$row['task_id']] = [];
                    }

                    $labels_data[$row['task_id']][$row['label_id']] = $row['label_name'];
                }
            }
        }

        return $labels_data;
    }

    /**
     * Query tracked time and estimates.
     *
     * @param  array $task_ids
     * @return array
     */
    public function queryTrackingData(User $user, $task_ids)
    {
        $include_tracking_data = false;
        $tracking_data = null;

        if ($this->getIncludeTrackingData() && ($user->isPowerUser() || $user->isFinancialManager()) && is_foreachable($task_ids)) {
            $include_tracking_data = true;

            if ($rows = DB::execute("SELECT parent_id, value AS 'tracked_time' FROM time_records WHERE is_trashed = ? AND parent_type = 'Task' AND parent_id IN (?) ORDER BY id", false, array_unique($task_ids))) {
                $rows->setCasting(['tracked_time' => DBResult::CAST_FLOAT]);

                foreach ($rows as $row) {
                    $assignment_id = $row['parent_id'];

                    if (isset($tracking_data[$assignment_id])) {
                        $tracking_data[$assignment_id]['tracked_time'] += time_to_minutes($row['tracked_time']);
                    } else {
                        $tracking_data[$assignment_id] = ['tracked_time' => time_to_minutes($row['tracked_time'])];
                    }
                }

                foreach ($tracking_data as $assignment => $value) {
                    $tracking_data[$assignment]['tracked_time'] = minutes_to_time($value['tracked_time']);
                }
            }
        }

        return [$include_tracking_data, $tracking_data];
    }

    // ---------------------------------------------------
    //  Conditions
    // ---------------------------------------------------

    /**
     * Group assignments based on given criteria.
     *
     * @param  string         $group_by
     * @param  User           $user
     * @param  DBResult|array $assignments
     * @param  array          $projects
     * @param  array          $labels
     * @param  array          $subtasks
     * @return array
     */
    private function groupAssignmentsInFirstWave($group_by, $user, $assignments, $projects, $labels, $subtasks)
    {
        $result = [];

        switch ($group_by) {
            case self::GROUP_BY_ASSIGNEE:
                $this->groupAssignmentsByAssignee($assignments, $result);

                break;
            case self::GROUP_BY_PROJECT:
                $this->groupAssignmentsByProject($assignments, $projects, $result);

                break;
            case self::GROUP_BY_PROJECT_CLIENT:
                $this->groupAssignmentsByProjectClient($assignments, $projects, $result);

                break;
            case self::GROUP_BY_LABEL:
                $this->groupAssignmentsByLabel($assignments, $result, $labels);

                break;
            case self::GROUP_BY_TASK_LIST:
                $this->groupAssignmentsByTaskList($assignments, $result);

                break;
            case self::GROUP_BY_CREATED_ON:
                $this->groupAssignmentsByCreatedOn($user, $assignments, $result);

                break;
            case self::GROUP_BY_DUE_ON:
                $this->groupAssignmentsByDueOn($user, $assignments, $subtasks, $result);

                break;
            case self::GROUP_BY_COMPLETED_ON:
                $this->groupAssignmentsByCompletedOn($user, $assignments, $result);

                break;
            default:
                $result['all'] = ['label' => lang('All Assignments'), 'assignments' => []];

                foreach ($assignments as $assignment) {
                    $result['all']['assignments'][$assignment['id']] = $assignment;
                }
        }

        return $result;
    }

    /**
     * @param array $assignments
     */
    private function groupAssignmentsByAssignee($assignments, array &$result)
    {
        $user_ids = [];

        foreach ($assignments as $assignment) {
            if ($assignment['assignee_id']) {
                $user_ids[] = $assignment['assignee_id'];
            }
        }

        if (count($user_ids)) {
            $user_id_name_map = Users::getIdNameMap(array_unique($user_ids));

            foreach ($user_id_name_map as $user_id => $user_name) {
                $result["user-$user_id"] = ['label' => $user_name, 'assignments' => []];
            }
        }

        $result['unknown-user'] = ['label' => lang('Unassigned'), 'assignments' => []];

        foreach ($assignments as $assignment) {
            $assignee_id = $assignment['assignee_id'];

            if (isset($result["user-$assignee_id"])) {
                $result["user-$assignee_id"]['assignments'][$assignment['id']] = $assignment;
            } else {
                $result['unknown-user']['assignments'][$assignment['id']] = $assignment;
            }
        }
    }

    /**
     * Group assignments by project.
     *
     * @param  array $assignments
     * @param  array $projects
     * @param  array $result
     * @return array
     */
    private function groupAssignmentsByProject($assignments, $projects, &$result)
    {
        if ($projects) {
            foreach ($projects as $k => $v) {
                $result["project-$k"] = [
                    'label' => $v,
                    'assignments' => [],
                ];
            }
        }

        $result['unknow-project'] = ['label' => lang('Unknown'), 'assignments' => []];

        foreach ($assignments as $assignment) {
            $project_id = $assignment['project_id'];

            if (isset($result["project-$project_id"])) {
                $result["project-$project_id"]['assignments'][$assignment['id']] = $assignment;
            } else {
                $result['unknow-project']['assignments'][$assignment['id']] = $assignment;
            }
        }
    }

    /**
     * @param array $assignments
     * @param array $projects
     */
    private function groupAssignmentsByProjectClient($assignments, $projects, array &$result)
    {
        $owner_company_id = AngieApplication::getContainer()
            ->get(OwnerCompanyResolverInterface::class)
                ->getId();
        $project_clients = null;

        if ($projects) {
            if ($rows = DB::execute("SELECT projects.id AS 'project_id', companies.id AS 'client_id', companies.name AS 'client_name' FROM projects, companies WHERE projects.company_id = companies.id AND projects.id IN (?) AND companies.id != ? ORDER BY companies.name", array_keys($projects), $owner_company_id)) {
                $project_clients = [];

                foreach ($rows as $row) {
                    $client_id = $row['client_id'];
                    $project_id = $row['project_id'];

                    $project_clients[$project_id] = $client_id;

                    if (empty($result["client-$client_id"])) {
                        $result["client-$client_id"] = ['label' => $row['client_name'], 'assignments' => []];
                    }
                }
            }
        }

        $result['internal-projects'] = ['label' => lang('Internal'), 'assignments' => []];

        foreach ($assignments as $assignment) {
            $project_id = (int) $assignment['project_id'];

            if (isset($project_clients[$project_id]) && $project_clients[$project_id]) {
                $result['client-' . $project_clients[$project_id]]['assignments'][$assignment['id']] = $assignment;
            } else {
                $result['internal-projects']['assignments'][$assignment['id']] = $assignment;
            }
        }
    }

    /**
     * Group assignments by label.
     *
     * @param array $assignments
     */
    public function groupAssignmentsByLabel($assignments, array &$result, array $labels = null)
    {
        $not_set = [];
        $label_ids = [];

        foreach ($assignments as $assignment) {
            $task_id = $assignment['id'];

            if ($labels === null) {
                $task_labels = $assignment['labels']; // Second wave, we have labels set as a property of each assignment
            } else {
                $task_labels = isset($labels[$task_id]) ? $labels[$task_id] : []; // First wave, we still have $assignments as DBResult with no extra properties
            }

            if (empty($task_labels)) {
                $not_set[$task_id] = $assignment;
            } else {
                foreach ($task_labels as $task_label) {
                    $key = 'label-' . trim(strtolower($task_label));

                    if (empty($result[$key])) {
                        $result[$key] = ['label' => $task_label, 'assignments' => []];
                    }

                    $result[$key]['assignments'][$task_id] = $assignment;
                }
            }
        }

        if (count($result)) {
            uasort($result, function ($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
        }

        if (count($not_set)) {
            $result['not-set'] = ['label' => lang('Not Set'), 'assignments' => $not_set];
        }
    }

    // ---------------------------------------------------
    //  Getters and Setters
    // ---------------------------------------------------

    /**
     * Group assignments by task list.
     *
     * @param array $assignments
     * @internal param bool $extend_task_list_name_when_grouping
     */
    public function groupAssignmentsByTaskList($assignments, array &$result)
    {
        $not_set = [];
        $task_list_ids = [];

        // Build assignments map
        foreach ($assignments as $assignment) {
            $task_list_id = $assignment['task_list_id'];

            if ($task_list_id) {
                $key = "task-list-{$task_list_id}";

                if (isset($result[$key])) {
                    $result[$key]['assignments'][$assignment['id']] = $assignment;
                } else {
                    $task_list_ids[] = $task_list_id;

                    $result[$key] = [
                        'label' => "Task List #{$task_list_id}",
                        'assignments' => [$assignment['id'] => $assignment],
                    ];
                }
            } else {
                $key = $assignment['project_id'];

                if (isset($not_set[$key])) {
                    $not_set[$key]['assignments'][$assignment['id']] = $assignment;
                } else {
                    $not_set[$key] = ['label' => $assignment['project_name'] . ' > ' . lang('Task List not Set'), 'assignments' => [$assignment['id'] => $assignment]];
                }
            }
        }

        // Now update names
        if ($task_list_ids) {
            if ($rows = DB::execute("SELECT task_lists.id, task_lists.name, task_lists.start_on, task_lists.due_on, task_lists.position, projects.id AS 'project_id', projects.name AS 'project_name' FROM task_lists LEFT OUTER JOIN projects ON task_lists.project_id = projects.id WHERE task_lists.id IN (?)", $task_list_ids)) {
                $rows->setCasting([
                    'start_on' => DBResult::CAST_DATE,
                    'due_on' => DBResult::CAST_DATE,
                    'position' => DBResult::CAST_INT,
                ]);

                foreach ($rows as $row) {
                    $label = $this->getExtendTaskListNameWhenGrouping() && $row['project_name'] ? $row['project_name'] . ' > ' . $row['name'] : $row['name'];
                    $result['task-list-' . $row['id']]['label'] = $label;
                    $result['task-list-' . $row['id']]['task_list_id'] = $row['id'];
                    $result['task-list-' . $row['id']]['project_id'] = $row['project_id'];
                    $result['task-list-' . $row['id']]['start_on'] = $row['start_on'];
                    $result['task-list-' . $row['id']]['due_on'] = $row['due_on'];
                    $result['task-list-' . $row['id']]['position'] = $row['position'];
                }
            }
        }

        if (count($not_set)) {
            if ($rows = DB::execute('SELECT id, name FROM projects WHERE id IN (?) AND is_trashed = ?', array_keys($not_set), false)) {
                foreach ($rows as $row) {
                    $not_set[$row['id']]['label'] = $row['name'] . ' > ' . lang('Task List not Set');
                }
            }

            foreach ($not_set as $k => $v) {
                $result["project-{$k}"] = $v;
            }
        }
    }

    /**
     * @param User  $user
     * @param array $assignments
     */
    private function groupAssignmentsByCreatedOn($user, $assignments, array &$result)
    {
        $unknown = [];

        foreach ($assignments as $assignment) {
            if ($assignment['created_on'] instanceof DateTimeValue) {
                $formatted_date = $assignment['created_on']->formatDateForUser($user);
                $timestamp = $assignment['created_on']->advance(Globalization::getUserGmtOffset(), false)->beginningOfDay()->getTimestamp();

                if (!isset($result[$timestamp])) {
                    $result[$timestamp] = [
                        'label' => (string) $formatted_date,
                        'assignments' => [],
                    ];
                }

                $result[$timestamp]['assignments'][$assignment['id']] = $assignment;
            } else {
                $unknown[$assignment['id']] = $assignment;
            }
        }

        // recently added tasks are at the top
        krsort($result, SORT_NUMERIC);

        $result['unknown'] = ['label' => lang('Unknown'), 'assignments' => $unknown];
    }

    /**
     * Group assignments by due date.
     *
     * @param  User  $user
     * @param  array $assignments
     * @param  array $subtasks
     * @return array
     */
    private function groupAssignmentsByDueOn($user, $assignments, $subtasks, array &$result)
    {
        $not_set = [];

        foreach ($assignments as $assignment) {
            if ($this->getIncludeSubtasks()) {
                $assignment_id = $assignment['id'];

                /** @var DateValue $reference_date */
                $reference_date = $assignment['due_on'];

                if ($subtasks && isset($subtasks[$assignment_id])) {
                    foreach ($subtasks[$assignment_id] as $subtask) {
                        if ($subtask['completed_on'] instanceof DateValue) {
                            continue; // Ignore completed subtasks
                        }

                        if (isset($subtask['due_on']) && $subtask['due_on'] instanceof DateValue) {
                            if (empty($reference_date) || $reference_date->getTimestamp() > $subtask['due_on']->getTimestamp()) {
                                $reference_date = $subtask['due_on'];
                            }
                        }
                    }
                }

                if ($reference_date instanceof DateValue) {
                    $formatted_date = lang('Due on :date', ['date' => $reference_date->formatForUser($user, 0)]);
                    $reference_timestamp = $reference_date->getTimestamp();
                } else {
                    $formatted_date = null;
                    $reference_timestamp = null;
                }
            } else {
                if ($assignment['due_on'] instanceof DateValue) {
                    $formatted_date = lang('Due on :date', ['date' => $assignment['due_on']->formatForUser($user, 0)]);
                    $reference_timestamp = $assignment['due_on']->getTimestamp();
                } else {
                    $formatted_date = null;
                    $reference_timestamp = null;
                }
            }

            if ($reference_timestamp && $formatted_date) {
                if (!isset($result[$reference_timestamp])) {
                    $result[$reference_timestamp] = [
                        'label' => $formatted_date,
                        'assignments' => [],
                    ];
                }

                $result[$reference_timestamp]['assignments'][$assignment['id']] = $assignment;
            } else {
                $not_set[$assignment['id']] = $assignment;
            }
        }

        ksort($result);

        $result['not-set'] = [
            'label' => lang('Due Date not Set'),
            'assignments' => $not_set,
        ];

        return $result;
    }

    /**
     * @param User  $user
     * @param array $assignments
     * @param array $result
     */
    private function groupAssignmentsByCompletedOn($user, $assignments, &$result)
    {
        $open_assignments = [];

        foreach ($assignments as $assignment) {
            if ($assignment['completed_on'] instanceof DateTimeValue) {
                $formatted_date = $assignment['completed_on']->formatDateForUser($user);
                $timestamp = $assignment['completed_on']->beginningOfDay()->advance(Globalization::getUserGmtOffset(), false)->getTimestamp();

                if (!isset($result[$timestamp])) {
                    $result[$timestamp] = ['label' => $formatted_date, 'assignments' => []];
                }

                $result[$timestamp]['assignments'][$assignment['id']] = $assignment;
            } else {
                $open_assignments[$assignment['id']] = $assignment;
            }
        }

        // most recently completed tasks are at the top
        krsort($result, SORT_NUMERIC);

        $result['open'] = ['label' => lang('Open'), 'assignments' => $open_assignments];
    }

    /**
     * Return subtask URL pattern.
     *
     * @param  int    $project_id
     * @param  int    $task_id
     * @param  int    $subtask_id
     * @return string
     */
    public function getSubtaskPermalink($project_id, $task_id, $subtask_id)
    {
        if (empty($this->subtask_url_pattern)) {
            $this->subtask_url_pattern = AngieApplication::getContainer()
                ->get(RouterInterface::class)
                    ->assemble(
                        'subtask',
                        [
                            'project_id' => '--PROJECT-ID--',
                            'task_id' => '--TASK-ID--',
                            'subtask_id' => '--SUBTASK-ID--',
                        ]
                    );
        }

        return str_replace(
            [
                '--PROJECT-ID--',
                '--TASK-ID--',
                '--SUBTASK-ID--',
            ],
            [
                $project_id,
                $task_id,
                $subtask_id,
            ],
            $this->subtask_url_pattern
        );
    }

    /**
     * Return task permalink.
     *
     * @param  array  $task
     * @return string
     */
    public function getTaskPermalink($task)
    {
        if (empty($this->task_url_pattern)) {
            $this->task_url_pattern = AngieApplication::getContainer()
                ->get(RouterInterface::class)
                    ->assemble(
                        'task',
                        [
                            'project_id' => '--PROJECT-ID--',
                            'task_id' => '--TASK-ID--',
                        ]
                    );
        }

        return str_replace(
            [
                '--PROJECT-ID--',
                '--TASK-ID--',
            ],
            [
                $task['project_id'],
                $task['id'],
            ],
            $this->task_url_pattern
        );
    }

    /**
     * Group assignments based on given criteria.
     *
     * @param  string         $group_by
     * @param  User           $user
     * @param  DBResult|array $assignments
     * @param  array          $projects
     * @param  array          $subtasks
     * @return array
     */
    private function groupAssignmentsInSecondWave($group_by, $user, &$assignments, $projects, $subtasks)
    {
        foreach (array_keys($assignments) as $k) {
            $result = [];

            switch ($group_by) {
                case self::GROUP_BY_ASSIGNEE:
                    $this->groupAssignmentsByAssignee($assignments[$k]['assignments'], $result);

                    break;
                case self::GROUP_BY_PROJECT:
                    $this->groupAssignmentsByProject($assignments[$k]['assignments'], $projects, $result);

                    break;
                case self::GROUP_BY_PROJECT_CLIENT:
                    $this->groupAssignmentsByProjectClient($assignments[$k]['assignments'], $projects, $result);

                    break;
                case self::GROUP_BY_LABEL:
                    $this->groupAssignmentsByLabel($assignments[$k]['assignments'], $result);

                    break;
                case self::GROUP_BY_TASK_LIST:
                    $this->groupAssignmentsByTaskList($assignments[$k]['assignments'], $result);

                    break;
                case self::GROUP_BY_CREATED_ON:
                    $this->groupAssignmentsByCreatedOn($user, $assignments[$k]['assignments'], $result);

                    break;
                case self::GROUP_BY_DUE_ON:
                    $this->groupAssignmentsByDueOn($user, $assignments[$k]['assignments'], $subtasks, $result);

                    break;
                case self::GROUP_BY_COMPLETED_ON:
                    $this->groupAssignmentsByCompletedOn($user, $assignments[$k]['assignments'], $result);

                    break;
                default:
                    return;
            }

            $assignments[$k]['assignments'] = $result;
        }
    }

    public function getExportColumns(): array
    {
        $result = [
            'Assignment ID',
            'Type',
            'Project ID',
            'Project',
            'Project Client ID',
            'Project Client Name',
            'Assignee ID',
            'Assignee',
            'Is Important',
            'Labels',
            'Task List ID',
            'Task List',
            'Created On',
            'Created By ID',
            'Created By',
            'Start On',
            'Due On',
            'Completed On',
            'Completed By ID',
            'Completed By',
            'Name',
            'Task Number',
            'Age',
        ];

        if ($this->getIncludeTrackingData()) {
            $result[] = 'Estimated Time';
            $result[] = 'Estimated Job Type';
            $result[] = 'Tracked Time';
        }

        return $result;
    }

    public function exportWriteLines(User $user, array $result): void
    {
        $include_tracking_data = $this->getIncludeTrackingData();

        foreach ($result as $v) {
            if ($v['assignments']) {
                foreach ($v['assignments'] as $assignment) {
                    $record = [
                        $assignment['id'],
                        $assignment['type'],
                        $assignment['project_id'],
                        $assignment['project'],
                        $assignment['client_id'],
                        ($assignment['client_name'] === 'Owner Company' ? 'Internal' : $assignment['client_name']),
                        $assignment['assignee_id'],
                        $assignment['assignee_id'] ? $assignment['assignee'] : null,
                        $assignment['is_important'],
                        is_array($assignment['labels']) && !empty($assignment['labels']) ? implode(',', $assignment['labels']) : null,
                        $assignment['task_list_id'],
                        $assignment['task_list_id'] ? $assignment['task_list'] : null,
                        $assignment['created_on'] instanceof DateTimeValue ? $assignment['created_on']->toMySQL() : null,
                        $assignment['created_by_id'],
                        $assignment['created_by_id'] ? $assignment['created_by'] : null,
                        $assignment['start_on'] instanceof DateValue ? $assignment['start_on']->toMySQL() : null,
                        $assignment['due_on'] instanceof DateValue ? $assignment['due_on']->toMySQL() : null,
                        $assignment['completed_on'] instanceof DateTimeValue ? $assignment['completed_on']->toMySQL() : null,
                        $assignment['completed_by_id'],
                        $assignment['completed_by_id'] ? $assignment['completed_by'] : null,
                        $assignment['name'],
                        ($assignment['type'] == Task::class ? $assignment['task_number'] : null),
                        $assignment['age'],
                    ];

                    if ($include_tracking_data) {
                        $record[] = $assignment['estimated_time'];
                        $record[] = $assignment['estimated_job_type_id'];
                        $record[] = $assignment['tracked_time'];
                    }

                    $this->exportWriteLine($record);

                    if (isset($assignment['subtasks']) && $assignment['subtasks']) {
                        foreach ($assignment['subtasks'] as $subtask) {
                            $subtask_record = [
                                $subtask['id'],
                                Subtask::class,
                                $assignment['project_id'],
                                $assignment['project'],
                                $subtask['assignee_id'],
                                $subtask['assignee_id'] ? $subtask['assignee'] : null,
                                !empty($subtask['is_important']),
                                null,
                                $assignment['task_list_id'],
                                $assignment['task_list_id'] ? $assignment['task_list'] : null,
                                $subtask['created_on'] instanceof DateTimeValue ? $subtask['created_on']->toMySQL() : null,
                                $subtask['created_by_id'],
                                $subtask['created_by_id'] ? $subtask['created_by'] : null,
                                $subtask['due_on'] instanceof DateValue ? $subtask['due_on']->toMySQL() : null,
                                $subtask['completed_on'] instanceof DateTimeValue ? $subtask['completed_on']->toMySQL() : null,
                                $subtask['completed_by_id'],
                                $subtask['completed_by_id'] ? $subtask['completed_by'] : null,
                                $subtask['body'],
                                null,
                            ];

                            if ($include_tracking_data) {
                                $subtask_record[] = null;
                                $subtask_record[] = null;
                                $subtask_record[] = null;
                            }

                            $this->exportWriteLine($subtask_record);
                        }
                    }
                }
            }
        }
    }

    public function canRun(User $user): bool
    {
        return true;
    }

    /**
     * @param  string $field_name
     * @return bool
     */
    public function calculateTimezoneWhenFilteringByDate($field_name)
    {
        if ($field_name == 'completed_on') {
            return true;
        }

        return parent::calculateTimezoneWhenFilteringByDate($field_name);
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        // User filter
        $result['user_filter'] = $this->getUserFilter();
        switch ($result['user_filter']) {
            case self::USER_FILTER_COMPANY_MEMBER:
            case self::USER_FILTER_COMPANY_MEMBER_RESPONSIBLE:
                $result['company_id'] = (int) $this->getUserFilterCompanyId();

                break;

            case self::USER_FILTER_SELECTED:
            case self::USER_FILTER_SELECTED_RESPONSIBLE:
                $result['user_ids'] = $this->getUserFilterSelectedUsers();

                break;
        }

        // Label filter
        $result['label_filter'] = $this->getLabelFilter();
        if ($result['label_filter'] == self::LABEL_FILTER_SELECTED || $result['label_filter'] == self::LABEL_FILTER_NOT_SELECTED) {
            $result['label_names'] = $this->getLabelNames();

            if (is_array($result['label_names'])) {
                sort($result['label_names']);
            } else {
                $result['label_names'] = [];
            }
        }

        // Task list filter
        $result['task_list_filter'] = $this->getTaskListFilter();
        if ($result['task_list_filter'] == self::TASK_LIST_FILTER_SELECTED) {
            $result['task_list_names'] = $this->getTaskListNames();
        }

        // Job type filter
        $result['job_type_filter'] = $this->getJobTypeFilter();
        if ($result['job_type_filter'] == self::JOB_TYPE_FILTER_SELECTED) {
            $result['job_type_ids'] = $this->getJobTypeIds();
        }

        $this->describeDateFilter('created', $result);
        $this->describeDateFilter('start', $result);
        $this->describeDateFilter('due', $result);
        $this->describeDateFilter('completed', $result);

        $this->describeUserFilter('created', $result);
        $this->describeUserFilter('delegated', $result);
        $this->describeUserFilter('completed', $result);

        // Project filter
        $result['project_filter'] = $this->getProjectFilter();
        switch ($this->getProjectFilter()) {
            case Projects::PROJECT_FILTER_CATEGORY:
                $result['project_category_id'] = $this->getProjectCategoryId();

                break;
            case Projects::PROJECT_FILTER_CLIENT:
                $result['project_client_id'] = $this->getProjectClientId();

                break;
            case Projects::PROJECT_FILTER_SELECTED:
                $result['project_ids'] = $this->getProjectIds();

                break;
        }

        $result['include_all_projects'] = (bool) $this->getIncludeAllProjects();
        $result['include_tracking_data'] = (bool) $this->getIncludeTrackingData();
        $result['include_subtasks'] = (bool) $this->getIncludeSubtasks();
        $result['is_private'] = (bool) $this->getIsPrivate();

        return $result;
    }

    /**
     * Return project filter value.
     *
     * @return string
     */
    public function getProjectFilter()
    {
        return $this->getAdditionalProperty('project_filter', Projects::PROJECT_FILTER_ANY);
    }

    /**
     * Return project category ID.
     *
     * @return int
     */
    public function getProjectCategoryId()
    {
        return (int) $this->getAdditionalProperty('project_category_id');
    }

    /**
     * Return project client ID.
     *
     * @return int
     */
    public function getProjectClientId()
    {
        return (int) $this->getAdditionalProperty('project_client_id');
    }

    /**
     * Return project ID-s.
     *
     * @return array
     */
    public function getProjectIds()
    {
        return $this->getAdditionalProperty('project_ids');
    }

    /**
     * Return true if system should search all project (admins and PM).
     *
     * @return bool
     */
    public function getIncludeAllProjects()
    {
        return $this->getAdditionalProperty('include_all_projects', false);
    }

    /**
     * Set non-field value during DataManager::create() and DataManager::update() calls.
     *
     * @param string $attribute
     * @param mixed  $value
     */
    public function setAttribute($attribute, $value)
    {
        switch ($attribute) {
            case 'user_filter':
                if (str_starts_with((string) $value, self::USER_FILTER_COMPANY_MEMBER)) {
                    $this->filterByCompany($this->getIdFromFilterValue($value), true);
                } elseif ((string) str_starts_with($value, self::USER_FILTER_SELECTED)) {
                    $this->filterByUsers($this->getIdsFromFilterValue($value), true);
                } else {
                    $this->setUserFilter($value);
                }

                break;
            case 'created_by_filter':
                $this->setUserFilterAttribute('created', $value);

                break;
            case 'delegated_by_filter':
                $this->setUserFilterAttribute('delegated', $value);

                break;
            case 'label_filter':
                if (str_starts_with((string) $value, self::LABEL_FILTER_SELECTED)) {
                    $this->filterByLabelNames($this->getNamesFromFilterValue($value, self::LABEL_FILTER_SELECTED));
                } elseif (str_starts_with((string) $value, self::LABEL_FILTER_NOT_SELECTED)) {
                    $this->filterByLabelNames($this->getNamesFromFilterValue($value, self::LABEL_FILTER_NOT_SELECTED), true);
                } else {
                    $this->setLabelFilter($value);
                }

                break;
            case 'task_list_filter':
                if (str_starts_with((string) $value, self::TASK_LIST_FILTER_SELECTED)) {
                    $this->filterByTaskListNames($this->getNamesFromFilterValue($value, self::TASK_LIST_FILTER_SELECTED));
                } else {
                    $this->setTaskListFilter($value);
                }

                break;
            case 'job_type_filter':
                if (str_starts_with((string) $value, self::JOB_TYPE_FILTER_SELECTED)) {
                    $this->filterByJobTypes($this->getIdsFromFilterValue($value));
                } else {
                    $this->setJobTypeFilter($value);
                }

                break;
            case 'created_on_filter':
                $this->setDateFilterAttribute('created', $value);

                break;
            case 'start_on_filter':
                $this->setDateFilterAttribute('start', $value);

                break;
            case 'due_on_filter':
                $this->setDateFilterAttribute('due', $value);

                break;
            case 'completed_on_filter':
                $this->setDateFilterAttribute('completed', $value);

                break;
            case 'completed_by_filter':
                $this->setUserFilterAttribute('completed', $value);

                break;
            case 'project_filter':
                if (str_starts_with((string) $value, Projects::PROJECT_FILTER_CATEGORY)) {
                    $this->filterByProjectCategory($this->getIdFromFilterValue($value));
                } elseif (str_starts_with((string) $value, Projects::PROJECT_FILTER_CLIENT)) {
                    $this->filterByProjectClient($this->getIdFromFilterValue($value));
                } elseif (str_starts_with((string) $value, Projects::PROJECT_FILTER_SELECTED)) {
                    $this->filterByProjects($this->getIdsFromFilterValue($value));
                } else {
                    $this->setProjectFilter($value);
                }

                break;
            case 'is_private':
            case 'include_all_projects':
            case 'include_tracking_data':
            case 'include_subtasks':
            case 'group_by':
                parent::setAttribute($attribute, $value);
        }
    }

    /**
     * Set filter by company values.
     *
     * @param int  $company_id
     * @param bool $responsible_only
     */
    public function filterByCompany($company_id, $responsible_only = false)
    {
        if ($responsible_only) {
            $this->setUserFilter(self::USER_FILTER_COMPANY_MEMBER_RESPONSIBLE);
        } else {
            $this->setUserFilter(self::USER_FILTER_COMPANY_MEMBER);
        }

        $this->setAdditionalProperty('company_id', $company_id);
    }

    /**
     * Set user filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setUserFilter($value)
    {
        return $this->setAdditionalProperty('user_filter', $value);
    }

    /**
     * Set user filter to filter only tracked object for selected users.
     *
     * @param array $users
     * @param bool  $responsible_only
     */
    public function filterByUsers($users, $responsible_only = false)
    {
        if ($responsible_only) {
            $this->setUserFilter(self::USER_FILTER_SELECTED_RESPONSIBLE);
        } else {
            $this->setUserFilter(self::USER_FILTER_SELECTED);
        }

        if (is_array($users)) {
            $user_ids = [];

            foreach ($users as $k => $v) {
                $user_ids[$k] = $v instanceof User ? $v->getId() : (int) $v;
            }
        } else {
            $user_ids = null;
        }

        $this->setAdditionalProperty('selected_users', $user_ids);
    }

    /**
     * Filter assignment by given list of labels.
     *
     * @param  array $label_names
     * @param  bool  $invert
     * @return array
     */
    public function filterByLabelNames($label_names, $invert = false)
    {
        if ($invert) {
            $this->setLabelFilter(self::LABEL_FILTER_NOT_SELECTED);
        } else {
            $this->setLabelFilter(self::LABEL_FILTER_SELECTED);
        }

        $this->setAdditionalProperty('label_names', $label_names);
    }

    /**
     * Set label filter.
     *
     * @param  string $value
     * @return string
     */
    public function setLabelFilter($value)
    {
        return $this->setAdditionalProperty('label_filter', $value);
    }

    /**
     * Filter assignment by given list of task lists.
     *
     * @param  array $task_list_names
     * @return array
     */
    public function filterByTaskListNames($task_list_names)
    {
        $this->setTaskListFilter(self::TASK_LIST_FILTER_SELECTED);
        $this->setAdditionalProperty('task_list_names', $task_list_names);
    }

    /**
     * Set task list filter.
     *
     * @param  string $value
     * @return string
     */
    public function setTaskListFilter($value)
    {
        return $this->setAdditionalProperty('task_list_filter', $value);
    }

    /**
     * Filter by a list of job types.
     */
    public function filterByJobTypes(array $job_type_ids)
    {
        $this->setJobTypeFilter(self::JOB_TYPE_FILTER_SELECTED);
        $this->setAdditionalProperty('job_type_ids', $job_type_ids);
    }

    /**
     * Set job type filter.
     *
     * @param  string $value
     * @return string
     */
    public function setJobTypeFilter($value)
    {
        return $this->setAdditionalProperty('job_type_filter', $value);
    }

    // ---------------------------------------------------
    //  Job type filter
    // ---------------------------------------------------

    /**
     * Set filter to filter records by project category.
     *
     * @param  int $project_category_id
     * @return int
     */
    public function filterByProjectCategory($project_category_id)
    {
        $this->setProjectFilter(Projects::PROJECT_FILTER_CATEGORY);
        $this->setAdditionalProperty('project_category_id', (int) $project_category_id);
    }

    /**
     * Set project filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setProjectFilter($value)
    {
        return $this->setAdditionalProperty('project_filter', $value);
    }

    /**
     * Set filter to filter records by project client.
     *
     * @param  Company|int $project_client_id
     * @return int
     */
    public function filterByProjectClient($project_client_id)
    {
        $this->setProjectFilter(Projects::PROJECT_FILTER_CLIENT);
        if ($project_client_id instanceof Company) {
            $this->setAdditionalProperty('project_client_id', $project_client_id->getId());
        } else {
            $this->setAdditionalProperty('project_client_id', (int) $project_client_id);
        }
    }

    /**
     * Set this report to filter records by project ID-s.
     *
     * @param  array $project_ids
     * @return array
     */
    public function filterByProjects($project_ids)
    {
        $this->setProjectFilter(Projects::PROJECT_FILTER_SELECTED);

        if (is_array($project_ids)) {
            foreach ($project_ids as $k => $v) {
                $project_ids[$k] = (int) $v;
            }
        } else {
            $project_ids = null;
        }

        $this->setAdditionalProperty('project_ids', $project_ids);
    }

    // ---------------------------------------------------
    //  Created on filter
    // ---------------------------------------------------

    /**
     * Return created by filter value.
     *
     * @return string
     */
    public function getCreatedByFilter()
    {
        return $this->getAdditionalProperty('created_by_filter', self::USER_FILTER_ANYBODY);
    }

    /**
     * Set filter by company values.
     *
     * @param int $company_id
     */
    public function createdByCompanyMember($company_id)
    {
        $this->setCreatedByFilter(self::USER_FILTER_COMPANY_MEMBER);
        $this->setAdditionalProperty('created_by_company_member_id', $company_id);
    }

    /**
     * Set created by filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setCreatedByFilter($value)
    {
        return $this->setAdditionalProperty('created_by_filter', $value);
    }

    /**
     * Return company ID set for user filter.
     *
     * @return int
     */
    public function getCreatedByCompanyMember()
    {
        return $this->getAdditionalProperty('created_by_company_member_id');
    }

    /**
     * Set user filter to filter only tracked object for selected users.
     *
     * $user_ids can be an array of user ID-s or a single user ID or NULL
     *
     * @param array $user_ids
     */
    public function createdByUsers($user_ids)
    {
        $this->setCreatedByFilter(self::USER_FILTER_SELECTED);

        if (is_array($user_ids)) {
            foreach ($user_ids as $k => $v) {
                $user_ids[$k] = (int) $v;
            }
        } elseif ($user_ids) {
            $user_ids = [$user_ids];
        } else {
            $user_ids = null;
        }

        $this->setAdditionalProperty('created_by_users', $user_ids);
    }

    /**
     * Return array of selected users.
     *
     * @return array
     */
    public function getCreatedByUsers()
    {
        return $this->getAdditionalProperty('created_by_users');
    }

    /**
     * Return delegated by filter value.
     *
     * @return string
     */
    public function getDelegatedByFilter()
    {
        return $this->getAdditionalProperty('delegated_by_filter', self::USER_FILTER_ANYBODY);
    }

    /**
     * Set delegated by company member filter.
     *
     * @param int $company_id
     */
    public function delegatedByCompanyMember($company_id)
    {
        $this->setDelegatedByFilter(self::USER_FILTER_COMPANY_MEMBER);

        $this->setAdditionalProperty('delegated_by_company_member_id', $company_id);
    }

    /**
     * Set delegated by filter.
     *
     * @param  string $value
     * @return string
     */
    public function setDelegatedByFilter($value)
    {
        return $this->setAdditionalProperty('delegated_by_filter', $value);
    }

    /**
     * Return company ID set for delegated by filter.
     *
     * @return int
     */
    public function getDelegatedByCompanyMember()
    {
        return $this->getAdditionalProperty('delegated_by_company_member_id');
    }

    /**
     * Set delegated by fileter to the list of users.
     *
     * $user_ids can be an array of user ID-s or a single user ID or NULL
     *
     * @param array $user_ids
     */
    public function delegatedByUsers($user_ids)
    {
        $this->setDelegatedByFilter(self::USER_FILTER_SELECTED);

        if (is_array($user_ids)) {
            foreach ($user_ids as $k => $v) {
                $user_ids[$k] = (int) $v;
            }
        } elseif ($user_ids) {
            $user_ids = [$user_ids];
        } else {
            $user_ids = null;
        }

        $this->setAdditionalProperty('delegated_by_users', $user_ids);
    }

    /**
     * Return array of selected users.
     *
     * @return array
     */
    public function getDelegatedByUsers()
    {
        return $this->getAdditionalProperty('delegated_by_users');
    }

    /**
     * Return created on filter value.
     *
     * @return string
     */
    public function getCreatedOnFilter()
    {
        return $this->getAdditionalProperty('created_on_filter', self::DATE_FILTER_ANY);
    }

    /**
     * @return int
     */
    public function getCreatedAge()
    {
        return (int) $this->getAdditionalProperty('created_age');
    }

    /**
     * Set created on age.
     *
     * @param  int    $value
     * @param  string $filter
     * @return string
     */
    public function createdAge($value, $filter = DataFilter::DATE_FILTER_AGE_IS)
    {
        if (
            $filter == DataFilter::DATE_FILTER_AGE_IS
            || DataFilter::DATE_FILTER_AGE_IS_LESS_THAN
            || $filter == DataFilter::DATE_FILTER_AGE_IS_MORE_THAN
        ) {
            $this->setCreatedOnFilter($filter);
        } else {
            throw new InvalidParamError('filter', $filter);
        }

        return $this->setAdditionalProperty('created_age', (int) $value);
    }

    /**
     * Set created on filter to a given $value.
     *
     * @param  string $value
     * @return string
     */
    public function setCreatedOnFilter($value)
    {
        return $this->setAdditionalProperty('created_on_filter', $value);
    }

    /**
     * Filter objects created on a given date.
     *
     * @param string $date
     */
    public function createdOnDate($date)
    {
        $this->setCreatedOnFilter(self::DATE_FILTER_SELECTED_DATE);
        $this->setAdditionalProperty('created_on_filter_on', (string) $date);
    }

    /**
     * Filter objects created before a given date (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function createdBeforeDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE : self::DATE_FILTER_BEFORE_SELECTED_DATE;
        $this->setCreatedOnFilter($filter);
        $this->setAdditionalProperty('created_on_filter_on', (string) $date);
    }

    /**
     * Filter objects created after a given date (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function createdAfterDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_AFTER_AND_ON_SELECTED_DATE : self::DATE_FILTER_AFTER_SELECTED_DATE;
        $this->setCreatedOnFilter($filter);
        $this->setAdditionalProperty('created_on_filter_on', (string) $date);
    }

    /**
     * Return selected date for created on filter.
     *
     * @return DateValue
     */
    public function getCreatedOnDate()
    {
        $on = $this->getAdditionalProperty('created_on_filter_on');

        return $on ? new DateValue($on) : null;
    }

    /**
     * Filter assignments created in a given range.
     *
     * @param string $from
     * @param string $to
     */
    public function createdInRange($from, $to)
    {
        $this->setCreatedOnFilter(self::DATE_FILTER_SELECTED_RANGE);
        $this->setAdditionalProperty('created_on_filter_from', (string) $from);
        $this->setAdditionalProperty('created_on_filter_to', (string) $to);
    }

    /**
     * Return created on filter range.
     *
     * @return array
     */
    public function getCreatedInRange()
    {
        $from = $this->getAdditionalProperty('created_on_filter_from');
        $to = $this->getAdditionalProperty('created_on_filter_to');

        return $from && $to ? [new DateValue($from), new DateValue($to)] : [null, null];
    }

    /**
     * Filter assignents that are due on a given date.
     *
     * @param string $date
     */
    public function dueOnDate($date)
    {
        $this->setDueOnFilter(self::DATE_FILTER_SELECTED_DATE);
        $this->setAdditionalProperty('due_on_filter_on', (string) $date);
    }

    /**
     * Set due date filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setDueOnFilter($value)
    {
        return $this->setAdditionalProperty('due_on_filter', $value);
    }

    /**
     * Filter assignents that are due on a given date (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function dueBeforeDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE : self::DATE_FILTER_BEFORE_SELECTED_DATE;
        $this->setDueOnFilter($filter);
        $this->setAdditionalProperty('due_on_filter_on', (string) $date);
    }

    /**
     * Filter assignents that are due on a given date (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function dueAfterDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_AFTER_AND_ON_SELECTED_DATE : self::DATE_FILTER_AFTER_SELECTED_DATE;
        $this->setDueOnFilter($filter);
        $this->setAdditionalProperty('due_on_filter_on', (string) $date);
    }

    /**
     * Return due on filter value.
     *
     * @return DateValue
     */
    public function getDueOnDate()
    {
        $on = $this->getAdditionalProperty('due_on_filter_on');

        return $on ? new DateValue($on) : null;
    }

    /**
     * Return assignments that are due in a given range.
     *
     * @param string $from
     * @param string $to
     */
    public function dueInRange($from, $to)
    {
        $this->setDueOnFilter(self::DATE_FILTER_SELECTED_RANGE);
        $this->setAdditionalProperty('due_on_filter_from', (string) $from);
        $this->setAdditionalProperty('due_on_filter_to', (string) $to);
    }

    /**
     * Return due on filter range.
     *
     * @return array
     */
    public function getDueInRange()
    {
        $from = $this->getAdditionalProperty('due_on_filter_from');
        $to = $this->getAdditionalProperty('due_on_filter_to');

        return $from && $to ? [new DateValue($from), new DateValue($to)] : [null, null];
    }

    /**
     * Return starts date filter value.
     *
     * @return string
     */
    public function getStartOnFilter()
    {
        return $this->getAdditionalProperty('start_on_filter', self::DATE_FILTER_ANY);
    }

    /**
     * Set starts date filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setStartOnFilter($value)
    {
        return $this->setAdditionalProperty('start_on_filter', $value);
    }

    /**
     * Filter assignments that starts on a given date.
     *
     * @param string $date
     */
    public function startOnDate($date)
    {
        $this->setStartOnFilter(self::DATE_FILTER_SELECTED_DATE);
        $this->setAdditionalProperty('start_on_filter_on', (string) $date);
    }

    /**
     * Filter assignments that starts before given date (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function startBeforeDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE : self::DATE_FILTER_BEFORE_SELECTED_DATE;
        $this->setStartOnFilter($filter);
        $this->setAdditionalProperty('start_on_filter_on', (string) $date);
    }

    /**
     * Filter assignments that starts after given date (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function startAfterDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_AFTER_AND_ON_SELECTED_DATE : self::DATE_FILTER_AFTER_SELECTED_DATE;
        $this->setStartOnFilter($filter);
        $this->setAdditionalProperty('start_on_filter_on', (string) $date);
    }

    /**
     * Return starts on filter value.
     *
     * @return DateValue
     */
    public function getStartOnDate()
    {
        $on = $this->getAdditionalProperty('start_on_filter_on');

        return $on ? new DateValue($on) : null;
    }

    /**
     * Return assignments that start in a given range.
     *
     * @param string $from
     * @param string $to
     */
    public function startInRange($from, $to)
    {
        $this->setStartOnFilter(self::DATE_FILTER_SELECTED_RANGE);
        $this->setAdditionalProperty('start_on_filter_from', (string) $from);
        $this->setAdditionalProperty('start_on_filter_to', (string) $to);
    }

    /**
     * Return starts on filter range.
     *
     * @return array
     */
    public function getStartInRange()
    {
        $from = $this->getAdditionalProperty('start_on_filter_from');
        $to = $this->getAdditionalProperty('start_on_filter_to');

        return $from && $to ? [new DateValue($from), new DateValue($to)] : [null, null];
    }

    /**
     * Return delegated by filter value.
     *
     * @return string
     */
    public function getCompletedByFilter()
    {
        return $this->getAdditionalProperty('completed_by_filter', self::USER_FILTER_ANYBODY);
    }

    /**
     * Set delegated by company member filter.
     *
     * @param int $company_id
     */
    public function completedByCompanyMember($company_id)
    {
        $this->setCompletedByFilter(self::USER_FILTER_COMPANY_MEMBER);
        $this->setAdditionalProperty('completed_by_company_member_id', $company_id);
    }

    /**
     * Set delegated by filter.
     *
     * @param  string $value
     * @return string
     */
    public function setCompletedByFilter($value)
    {
        return $this->setAdditionalProperty('completed_by_filter', $value);
    }

    /**
     * Return company ID set for delegated by filter.
     *
     * @return int
     */
    public function getCompletedByCompanyMember()
    {
        return $this->getAdditionalProperty('completed_by_company_member_id');
    }

    /**
     * Set delegated by fileter to the list of users.
     *
     * $user_ids can be an array of user ID-s or a single user ID or NULL
     *
     * @param array $user_ids
     */
    public function completedByUsers($user_ids)
    {
        $this->setCompletedByFilter(self::USER_FILTER_SELECTED);

        if (is_array($user_ids)) {
            foreach ($user_ids as $k => $v) {
                $user_ids[$k] = (int) $v;
            }
        } elseif ($user_ids) {
            $user_ids = [$user_ids];
        } else {
            $user_ids = null;
        }

        $this->setAdditionalProperty('completed_by_users', $user_ids);
    }

    /**
     * Return array of selected users.
     *
     * @return array
     */
    public function getCompletedByUsers()
    {
        return $this->getAdditionalProperty('completed_by_users');
    }

    /**
     * Return completed on filter value.
     *
     * @return string
     */
    public function getCompletedOnFilter()
    {
        return $this->getAdditionalProperty('completed_on_filter', self::DATE_FILTER_ANY);
    }

    /**
     * Filter assignments that are completed on a given date.
     *
     * @param string $date
     */
    public function completedOnDate($date)
    {
        $this->setCompletedOnFilter(self::DATE_FILTER_SELECTED_DATE);
        $this->setAdditionalProperty('completed_filter_on', (string) $date);
    }

    /**
     * Set completed on filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setCompletedOnFilter($value)
    {
        return $this->setAdditionalProperty('completed_on_filter', $value);
    }

    /**
     * Completed before a given date (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function completedBeforeDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE : self::DATE_FILTER_BEFORE_SELECTED_DATE;
        $this->setCompletedOnFilter($filter);
        $this->setAdditionalProperty('completed_filter_on', (string) $date);
    }

    /**
     * Completed after a given date (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function completedAfterDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_AFTER_AND_ON_SELECTED_DATE : self::DATE_FILTER_AFTER_SELECTED_DATE;
        $this->setCompletedOnFilter($filter);
        $this->setAdditionalProperty('completed_filter_on', (string) $date);
    }

    /**
     * Return completed on filter value.
     *
     * @return DateValue
     */
    public function getCompletedOnDate()
    {
        $on = $this->getAdditionalProperty('completed_filter_on');

        return $on ? new DateValue($on) : null;
    }

    /**
     * Return assignments filter on a given range.
     *
     * @param string $from
     * @param string $to
     */
    public function completedInRange($from, $to)
    {
        $this->setCompletedOnFilter(self::DATE_FILTER_SELECTED_RANGE);
        $this->setAdditionalProperty('completed_on_filter_from', (string) $from);
        $this->setAdditionalProperty('completed_on_filter_to', (string) $to);
    }

    /**
     * Return value of completed filter.
     *
     * @return array
     */
    public function getCompletedInRange()
    {
        $from = $this->getAdditionalProperty('completed_on_filter_from');
        $to = $this->getAdditionalProperty('completed_on_filter_to');

        return $from && $to ? [new DateValue($from), new DateValue($to)] : [null, null];
    }

    public function canBeGroupedBy(): array
    {
        return [
            self::GROUP_BY_ASSIGNEE,
            self::GROUP_BY_PROJECT,
            self::GROUP_BY_PROJECT_CLIENT,
            self::GROUP_BY_LABEL,
            self::GROUP_BY_TASK_LIST,
            self::GROUP_BY_CREATED_ON,
            self::GROUP_BY_DUE_ON,
            self::GROUP_BY_COMPLETED_ON,
        ];
    }

    public function getGroupingMaxLevel(): int
    {
        return 2;
    }

    /**
     * Set whether system should include all projects (admins and PM).
     *
     * @param  bool $value
     * @return bool
     */
    public function setIncludeAllProjects($value)
    {
        return $this->setAdditionalProperty('include_all_projects', (bool) $value);
    }

    /**
     * Set include subtasks flag.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIncludeSubtasks($value)
    {
        return $this->setAdditionalProperty('include_subtasks', (bool) $value);
    }

    /**
     * Set include tracking data flag.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIncludeTrackingData($value)
    {
        return $this->setAdditionalProperty('include_tracking_data', (bool) $value);
    }

    /**
     * Set extend task list name when grouping (label will be in format 'project name > task list name' if set to true).
     *
     * @param $value
     * @return mixed
     */
    public function setExtendTaskListNameWhenGrouping($value)
    {
        return $this->setAdditionalProperty('extend_task_list_name_when_grouping', (bool) $value);
    }

    /**
     * Return should task list label be extended (label will be in format 'project name > task list name' if true).
     *
     * @return mixed
     */
    public function getExtendTaskListNameWhenGrouping()
    {
        return $this->getAdditionalProperty('extend_task_list_name_when_grouping', true);
    }
}
