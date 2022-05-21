<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;

class ProjectsFilter extends DataFilter
{
    // Client filter
    const CLIENT_FILTER_ANY = 'any';
    const CLIENT_FILTER_INTERNAL = 'internal';
    const CLIENT_FILTER_CLIENTS = 'clients';
    const CLIENT_FILTER_SELECTED = 'selected';

    // Category filter
    const CATEGORY_FILTER_ANY = 'any';
    const CATEGORY_FILTER_IS_SET = 'is_set';
    const CATEGORY_FILTER_IS_NOT_SET = 'is_not_set';
    const CATEGORY_FILTER_SELECTED = 'selected';
    const CATEGORY_FILTER_NOT_SELECTED = 'not_selected';

    // Label filter
    const LABEL_FILTER_ANY = 'any';
    const LABEL_FILTER_IS_SET = 'is_set';
    const LABEL_FILTER_IS_NOT_SET = 'is_not_set';
    const LABEL_FILTER_SELECTED = 'selected';
    const LABEL_FILTER_NOT_SELECTED = 'not_selected';
    const GROUP_BY_STATUS = 'status';
    const GROUP_BY_CLIENT = 'client';
    const GROUP_BY_CATEGORY = 'category';
    const GROUP_BY_LABEL = 'label';

    // ---------------------------------------------------
    //  Query and Group the Data
    // ---------------------------------------------------

    const GROUP_BY_LEADER = 'leader';
    const GROUP_BY_CREATED_ON = 'created_on';
    const GROUP_BY_COMPLETED_ON = 'completed_on';

    private ?string $project_url_pattern = null;

    public function run(User $user, array $additional = null): ?array
    {
        [
            $projects,
            $companies,
            $categories,
            $labels,
        ] = $this->queryProjectsData($user);

        if ($projects instanceof DBResult) {
            $group_by = $this->getGroupBy();
            $result = [];

            $this->groupProjects(array_shift($group_by), $user, $projects, $companies, $result);

            $include_budget_data = $this->getIncludeBudgetData();

            foreach (array_keys($result) as $k) {
                if ($result[$k]['projects']) {
                    foreach (array_keys($result[$k]['projects']) as $project_id) {
                        $this->prepareRecordDetails($result[$k]['projects'][$project_id], $companies, $categories, $labels, $user, $include_budget_data);
                    }
                }
            }

            if (count($group_by) > 0) {
                $this->groupProjectsSecondWave(array_shift($group_by), $user, $result, $companies);
            }

            return $result;
        }

        return null;
    }

    public function getProjectPermalink(array $project): string
    {
        if (empty($this->project_url_pattern)) {
            $this->project_url_pattern = AngieApplication::getContainer()
                ->get(RouterInterface::class)
                    ->assemble(
                        'project',
                        [
                            'project_id' => '--PROJECT-ID--',
                        ]
                    );
        }

        return str_replace(['--PROJECT-ID--'], [$project['id']], $this->project_url_pattern);
    }

    /**
     * Query projects data.
     *
     * @return array
     */
    protected function queryProjectsData(User $user)
    {
        try {
            $conditions = $this->prepareConditions($user);
        } catch (DataFilterConditionsError $e) {
            $conditions = null;
        }

        $order_by = 'name';

        $companies = [];
        $categories = [];
        $labels = [];

        if ($conditions) {
            $fields = [
                'id',
                'company_id',
                'category_id',
                'label_id',
                'leader_id',
                'name',
                'body',
                'created_on',
                'created_by_id',
                'created_by_name',
                'created_by_email',
                'completed_on',
                'completed_by_id',
                'completed_by_name',
                'completed_by_email',
                'project_number',
            ];

            if ($this->getIncludeBudgetData()) {
                $fields = array_merge(
                    $fields,
                    [
                        'currency_id',
                        'budget',
                        "'0' AS 'cost_so_far'",
                        'is_tracking_enabled',
                    ]
                );
            }

            $projects = DB::execute(
                sprintf(
                    'SELECT %s FROM `projects` WHERE %s ORDER BY %s',
                    implode(', ', $fields),
                    $conditions,
                    $order_by,
                )
            );

            if ($projects) {
                $projects->setCasting(
                    [
                        'created_on' => DBResult::CAST_DATETIME,
                        'completed_on' => DBResult::CAST_DATETIME,
                    ]
                );

                if ($this->getIncludeBudgetData()) {
                    $projects->setCasting(
                        [
                            'budget' => DBResult::CAST_FLOAT,
                            'cost_so_far' => DBResult::CAST_FLOAT,
                        ]
                    );
                }

                foreach ($projects as $project) {
                    if ($project['company_id'] && !in_array($project['company_id'], $companies)) {
                        $companies[$project['company_id']] = null;
                    }
                    if ($project['category_id'] && !in_array($project['category_id'], $categories)) {
                        $categories[] = $project['category_id'];
                    }
                    if ($project['label_id'] && !in_array($project['label_id'], $labels)) {
                        $labels[] = $project['label_id'];
                    }
                }
            }
        } else {
            $projects = null;
        }

        $companies = count($companies) ? Companies::getIdNameMap(array_keys($companies)) : null;
        $all_categories = Categories::getIdNameMap(null, ProjectCategory::class);

        if ($categories && $all_categories) {
            $categories = array_intersect_key($all_categories, array_flip($categories));
        }

        if ($labels && $all_labels = Labels::getIdNameMap(ProjectLabel::class)) {
            $labels = array_intersect_key($all_labels, array_flip($labels));
        }

        return [
            $projects,
            $companies,
            $categories,
            $labels,
        ];
    }

    /**
     * Prepare filter conditions.
     *
     * @return string
     */
    protected function prepareConditions(User $user)
    {
        $conditions = [DB::prepare('(projects.is_trashed = ?)', false)];

        if (!($user instanceof Owner && $this->getIncludeAllProjects())) {
            if ($project_ids = Projects::findIdsByUser($user, false)) {
                $conditions[] = DB::prepare('(projects.id IN (?))', $project_ids);
            } else {
                throw new DataFilterConditionsError('include_all_projects', 'include_all_projects', 'include_all_projects', "User can't access any of the projects");
            }
        }

        $this->prepareUserFilterConditions($user, 'lead', 'projects', $conditions, 'leader_id');
        $this->prepareClientFilterConditions($user, $conditions);
        $this->prepareCategoryFilterConditions($conditions);
        $this->prepareLabelFilterConditions($conditions);
        $this->prepareDateFilterConditions($user, 'created', 'projects', $conditions);
        $this->prepareDateFilterConditions($user, 'completed', 'projects', $conditions);

        return implode(' AND ', $conditions);
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
     * Prepare client filter conditions.
     */
    private function prepareClientFilterConditions(User $user, array & $conditions)
    {
        if ($this->getClientFilter() == self::CLIENT_FILTER_INTERNAL) {
            $conditions[] = DB::prepare(
                '(projects.company_id = ?)',
                AngieApplication::getContainer()
                    ->get(OwnerCompanyResolverInterface::class)
                        ->getId()
            );
        } else {
            if ($this->getClientFilter() == self::CLIENT_FILTER_CLIENTS) {
                $conditions[] = DB::prepare(
                    '(projects.company_id != ?)',
                    AngieApplication::getContainer()
                        ->get(OwnerCompanyResolverInterface::class)
                            ->getId()
                );
            } else {
                if ($this->getClientFilter() == self::CLIENT_FILTER_SELECTED) {
                    $client = DataObjectPool::get(Company::class, $this->getClientId());

                    if ($client) {
                        $conditions[] = DB::prepare('(projects.company_id = ?)', $client->getId());
                    } else {
                        throw new DataFilterConditionsError('client_filter', $this->getClientFilter(), $this->getClientId(), 'Client not found');
                    }
                } else {
                    if ($this->getClientFilter() != self::CLIENT_FILTER_ANY) {
                        throw new DataFilterConditionsError('client_filter', $this->getClientFilter(), 'mixed', 'Unknown client filter');
                    }
                }
            }
        }
    }

    /**
     * Return client filter.
     *
     * @return string
     */
    public function getClientFilter()
    {
        return $this->getAdditionalProperty('client_filter', self::CLIENT_FILTER_ANY);
    }

    /**
     * Return client ID.
     *
     * @return int
     */
    public function getClientId()
    {
        return (int) $this->getAdditionalProperty('client_id');
    }

    /**
     * Prepare category filter conditions.
     */
    private function prepareCategoryFilterConditions(array & $conditions)
    {
        if ($this->getCategoryFilter() == self::CATEGORY_FILTER_IS_SET) {
            $conditions[] = "(projects.category_id > '0')";
        } else {
            if ($this->getCategoryFilter() == self::CATEGORY_FILTER_IS_NOT_SET) {
                $conditions[] = "(projects.category_id = '0')";
            } else {
                if ($this->getCategoryFilter() == self::CATEGORY_FILTER_SELECTED || $this->getCategoryFilter() == self::CATEGORY_FILTER_NOT_SELECTED) {
                    $category_names = $this->getCategoryNames();

                    if ($category_names && is_foreachable($category_names)) {
                        $category_ids = Categories::getIdsByNames($category_names, 'ProjectCategory');

                        if ($category_ids && is_foreachable($category_ids)) {
                            if ($this->getCategoryFilter() == self::CATEGORY_FILTER_SELECTED) {
                                $conditions[] = DB::prepare('(projects.category_id IN (?))', $category_ids);
                            } else {
                                $conditions[] = DB::prepare('(projects.category_id NOT IN (?))', $category_ids);
                            }
                        } else {
                            throw new DataFilterConditionsError('category_filter', $this->getCategoryFilter(), $category_names, 'Categories not found');
                        }
                    } else {
                        throw new DataFilterConditionsError('category_filter', $this->getCategoryFilter(), $category_names, 'Invalid category names value');
                    }
                } else {
                    if ($this->getCategoryFilter() != self::CATEGORY_FILTER_ANY) {
                        throw new DataFilterConditionsError('category_filter', $this->getCategoryFilter(), 'mixed', 'Unknown category filter');
                    }
                }
            }
        }
    }

    /**
     * Return category filter.
     *
     * @return string
     */
    public function getCategoryFilter()
    {
        return $this->getAdditionalProperty('category_filter', self::CATEGORY_FILTER_ANY);
    }

    /**
     * Return category names.
     *
     * @return string
     */
    public function getCategoryNames()
    {
        return $this->getAdditionalProperty('category_names');
    }

    /**
     * Prepare label filter conditions.
     */
    private function prepareLabelFilterConditions(array & $conditions)
    {
        if ($this->getLabelFilter() == self::LABEL_FILTER_IS_SET) {
            $conditions[] = "(projects.label_id > '0')";
        } else {
            if ($this->getLabelFilter() == self::LABEL_FILTER_IS_NOT_SET) {
                $conditions[] = "(projects.label_id = '0')";
            } else {
                if ($this->getLabelFilter() == self::LABEL_FILTER_SELECTED || $this->getLabelFilter() == self::LABEL_FILTER_NOT_SELECTED) {
                    $label_names = $this->getLabelNames();

                    if ($label_names && is_foreachable($label_names)) {
                        $label_ids = Labels::getIdsByNames($label_names, 'ProjectLabel');

                        if ($label_ids && is_foreachable($label_ids)) {
                            if ($this->getLabelFilter() == self::LABEL_FILTER_SELECTED) {
                                $conditions[] = DB::prepare('(projects.label_id IN (?))', $label_ids);
                            } else {
                                $conditions[] = DB::prepare('(projects.label_id NOT IN (?))', $label_ids);
                            }
                        } else {
                            throw new DataFilterConditionsError('label_filter', $this->getLabelFilter(), $label_names, 'Labels not found');
                        }
                    } else {
                        throw new DataFilterConditionsError('label_filter', $this->getLabelFilter(), $label_names, 'Invalid label names value');
                    }
                } else {
                    if ($this->getLabelfilter() != self::LABEL_FILTER_ANY) {
                        throw new DataFilterConditionsError('label_filter', $this->getLabelfilter(), 'mixed', 'Unknown label filter');
                    }
                }
            }
        }
    }

    // ---------------------------------------------------
    //  Group by
    // ---------------------------------------------------

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
     * Return label names.
     *
     * @return string
     */
    public function getLabelNames()
    {
        return $this->getAdditionalProperty('label_names');
    }

    /**
     * Returns true if this filter also needs to return budget data.
     *
     * @return bool
     */
    public function getIncludeBudgetData()
    {
        return $this->getAdditionalProperty('include_budget_data', false);
    }

    /**
     * Group projects.
     *
     * @param  string         $group_by
     * @param  DBResult|array $projects
     * @param  array          $companies
     * @return array
     */
    protected function groupProjects($group_by, User $user, $projects, $companies, array & $result)
    {
        switch ($group_by) {
            case self::GROUP_BY_STATUS:
                $this->groupByStatus($projects, $result);

                break;
            case self::GROUP_BY_CLIENT:
                $this->groupByClient($projects, $companies, $result);

                break;
            case self::GROUP_BY_CATEGORY:
                $this->groupByCategory($projects, $result);

                break;
            case self::GROUP_BY_LABEL:
                $this->groupByLabel($projects, $result);

                break;
            case self::GROUP_BY_LEADER:
                $this->groupByLeader($projects, $result);

                break;
            case self::GROUP_BY_CREATED_ON:
                $this->groupByDateField($projects, $user, 'created_on', $result);

                break;
            case self::GROUP_BY_COMPLETED_ON:
                $this->groupByDateField($projects, $user, 'completed_on', $result);

                break;
            default:
                $this->groupUngrouped($projects, $result);
        }
    }

    /**
     * Return projects grouped by status.
     *
     * @param array $projects
     */
    private function groupByStatus($projects, array & $result)
    {
        $result = [
            'open' => ['label' => lang('Open'), 'projects' => []],
            'completed' => ['label' => lang('Completed'), 'projects' => []],
        ];

        foreach ($projects as $project) {
            if ($project['completed_on']) {
                $result['completed']['projects'][$project['id']] = $project;
            } else {
                $result['open']['projects'][$project['id']] = $project;
            }
        }

        if (empty($result['open']['projects'])) {
            unset($result['open']);
        }

        if (empty($result['completed']['projects'])) {
            unset($result['completed']);
        }
    }

    /**
     * Return projects grouped by client company.
     *
     * @param array $projects
     * @param array $companies
     */
    private function groupByClient($projects, $companies, array & $result)
    {
        if ($companies) {
            foreach ($companies as $k => $v) {
                $result["company-$k"] = ['label' => $v, 'projects' => []];
            }
        }

        $result['unknow-company'] = ['label' => lang('Unknown'), 'projects' => []];

        foreach ($projects as $project) {
            $company_id = $project['company_id'];

            if (isset($result["company-$company_id"])) {
                $result["company-$company_id"]['projects'][$project['id']] = $project;
            } else {
                $result['unknow-company']['projects'][$project['id']] = $project;
            }
        }

        if (empty($result['unknow-company']['projects'])) {
            unset($result['unknow-company']);
        }
    }

    /**
     * Return projects grouped by category.
     *
     * @param array $projects
     */
    private function groupByCategory($projects, array & $result)
    {
        if ($categories = Categories::getIdNameMap(null, ProjectCategory::class)) {
            foreach ($categories as $k => $v) {
                $result["category-$k"] = ['label' => $v, 'projects' => []];
            }
        }

        $result['category-not-set'] = ['label' => lang('Not Set'), 'projects' => []];

        foreach ($projects as $project) {
            $category_id = $project['category_id'];

            if (isset($result["category-$category_id"])) {
                $result["category-$category_id"]['projects'][$project['id']] = $project;
            } else {
                $result['category-not-set']['projects'][$project['id']] = $project;
            }
        }

        foreach (array_keys($result) as $k) {
            if (empty($result[$k]['projects'])) {
                unset($result[$k]);
            }
        }
    }

    /**
     * Return projects grouped by label.
     *
     * @param array $projects
     */
    private function groupByLabel($projects, array & $result)
    {
        if ($labels = Labels::getIdNameMap(ProjectLabel::class)) {
            foreach ($labels as $k => $v) {
                $result["label-$k"] = ['label' => $v[0], 'projects' => []];
            }
        }

        $result['label-not-set'] = ['label' => lang('Not Set'), 'projects' => []];

        foreach ($projects as $project) {
            $label_id = $project['label_id'];

            if (isset($result["label-$label_id"])) {
                $result["label-$label_id"]['projects'][$project['id']] = $project;
            } else {
                $result['label-not-set']['projects'][$project['id']] = $project;
            }
        }

        foreach ($result as $k => $v) {
            if (empty($v['projects'])) {
                unset($result[$k]);
            }
        }
    }

    /**
     * Return projects grouped by leader.
     *
     * @param array $records
     */
    private function groupByLeader($records, array & $result)
    {
        $this->groupByUser($records, $result, 'leader_id', lang('Not Set'), 'projects');
    }

    // ---------------------------------------------------
    //  Attributes
    // ---------------------------------------------------

    /**
     * Return projects grouped by a specific date field.
     *
     * @param array  $projects
     * @param User   $user
     * @param string $date_field
     */
    private function groupByDateField($projects, $user, $date_field, array & $result)
    {
        $offset = \Angie\Globalization::getUserGmtOffset($user);

        if ($date_field == 'completed_on') {
            $not_set_label = lang('Open Projects');
        } else {
            $not_set_label = lang('Not Set');
        }

        $date_not_set = ['label' => $not_set_label, 'projects' => []];

        foreach ($projects as $project) {
            $date = $project[$date_field];

            if ($date instanceof DateValue) {
                $key = 'date-' . date('Y-m-d', $date->getTimestamp() + $offset);

                if (empty($result[$key])) {
                    $result[$key] = ['label' => $date->formatDateForUser($user, 0), 'projects' => []];
                }

                $result[$key]['projects'][$project['id']] = $project;
            } else {
                $date_not_set['projects'][$project['id']] = $project;
            }
        }

        krsort($result);

        if (count($date_not_set['projects'])) {
            $result['date-not-set'] = $date_not_set;
        }
    }

    // ---------------------------------------------------
    //  Leader filter
    // ---------------------------------------------------

    /**
     * Return projects grouped in All group (ungrouped).
     *
     * @param array $projects
     */
    private function groupUngrouped($projects, array & $result)
    {
        $result['all'] = ['label' => lang('All Projects'), 'projects' => []];

        foreach ($projects as $project) {
            $result['all']['projects'][$project['id']] = $project;
        }
    }

    /**
     * Prepare details of each individual record.
     *
     * @param array $companies
     * @param array $categories
     * @param array $labels
     * @param bool  $include_budget_data
     */
    protected function prepareRecordDetails(array & $project, $companies, $categories, $labels, User $user, $include_budget_data = false)
    {
        $company_id = array_var($project, 'company_id', 0);
        $category_id = array_var($project, 'category_id', 0);
        $label_id = array_var($project, 'label_id', 0);

        if ($company_id && isset($companies[$company_id])) {
            $project['company_name'] = $companies[$company_id];
        } else {
            $project['company_name'] = lang('N/A');
        }

        if ($category_id && isset($categories[$category_id])) {
            $project['category_name'] = $categories[$category_id];
        } else {
            $project['category_name'] = lang('N/A');
        }

        if ($label_id && isset($labels[$label_id])) {
            $project['label_name'] = $labels[$label_id][0];
        } else {
            $project['label_name'] = lang('N/A');
        }

        $project['leader'] = $project['leader_id'] ? $this->getUserDisplayName($project['leader_id']) : '';
        $project['created_by'] = !empty($project['created_by_id']) ? $this->getUserDisplayName($project['created_by_id'], ['full_name' => $project['created_by_name'], 'email' => $project['created_by_email']]) : '';
        $project['completed_by'] = !empty($project['completed_by_id']) ? $this->getUserDisplayName($project['completed_by_id'], ['full_name' => $project['completed_by_name'], 'email' => $project['completed_by_email']]) : '';

        /** @var Project $project_instance */
        if ($include_budget_data && $project['is_tracking_enabled'] && $project_instance = DataObjectPool::get('Project', $project['id'])) {
            $project['cost_so_far'] = TrackingObjects::sumCostByProject($project_instance, $user);
        }

        $project['permalink'] = $this->getProjectPermalink($project);
    }

    /**
     * Group projects in second wave.
     *
     * @param  string $group_by
     * @param  array  $projects
     * @param  array  $companies
     * @return array
     */
    public function groupProjectsSecondWave($group_by, User $user, & $projects, $companies)
    {
        foreach (array_keys($projects) as $k) {
            $result = [];

            switch ($group_by) {
                case self::GROUP_BY_STATUS:
                    $this->groupByStatus($projects[$k]['projects'], $result);

                    break;
                case self::GROUP_BY_CLIENT:
                    $this->groupByClient($projects[$k]['projects'], $companies, $result);

                    break;
                case self::GROUP_BY_CATEGORY:
                    $this->groupByCategory($projects[$k]['projects'], $result);

                    break;
                case self::GROUP_BY_LABEL:
                    $this->groupByLabel($projects[$k]['projects'], $result);

                    break;
                case self::GROUP_BY_LEADER:
                    $this->groupByLeader($projects[$k]['projects'], $result);

                    break;
                case self::GROUP_BY_CREATED_ON:
                    $this->groupByDateField($projects[$k]['projects'], $user, 'created_on', $result);

                    break;
                case self::GROUP_BY_COMPLETED_ON:
                    $this->groupByDateField($projects[$k]['projects'], $user, 'completed_on', $result);

                    break;
                default:
                    return;
            }

            $projects[$k]['projects'] = $result;
        }
    }
    public function getExportColumns(): array
    {
        $result = [
            'Project ID',
            'Name',
            'Company ID',
            'Company',
            'Leader ID',
            'Leader',
            'Label ID',
            'Label',
            'Category ID',
            'Category',
            'Created On',
            'Created By ID',
            'Created By',
            'Completed On',
            'Completed By ID',
            'Completed By',
        ];

        if ($this->getIncludeBudgetData()) {
            $result[] = 'Currency Name';
            $result[] = 'Currency Code';
            $result[] = 'Budget';
            $result[] = 'Cost so Far';
        }

        $result[] = 'Project Number';
        $result[] = 'Permalink';

        return $result;
    }

    public function exportWriteLines(User $user, array $result): void
    {
        $labels = Labels::getIdNameMap(ProjectLabel::class);
        $categories = Categories::getIdNameMap(null, ProjectCategory::class);

        $include_budget_data = $this->getIncludeBudgetData();

        $currencies = $include_budget_data ? Currencies::getIdDetailsMap() : null;

        foreach ($result as $v) {
            if ($v['projects']) {
                foreach ($v['projects'] as $project) {
                    $label_id = $project['label_id'];
                    $category_id = $project['category_id'];

                    $line = [
                        $project['id'],
                        $project['name'],
                        $project['company_id'] ? $project['company_id'] : 0,
                        $project['company_name'] ? $project['company_name'] : '',
                        $project['leader_id'],
                        $project['leader'],
                        $label_id,
                        $label_id && !empty($labels[$label_id]) && !empty($labels[$label_id][0]) ? $labels[$label_id][0] : null,
                        $category_id,
                        $category_id && isset($categories[$category_id]) ? $categories[$category_id] : null,
                        $project['created_on'] instanceof DateValue ? $project['created_on']->toMySQL() : null,
                        $project['created_by_id'],
                        $project['created_by'],
                        $project['completed_on'] instanceof DateValue ? $project['completed_on']->toMySQL() : null,
                        $project['completed_by_id'],
                        $project['completed_by'],
                    ];

                    if ($include_budget_data) {
                        $currency_id = $project['currency_id'];

                        $line[] = $currency_id && isset($currencies[$currency_id]) ? $currencies[$currency_id]['name'] : null;
                        $line[] = $currency_id && isset($currencies[$currency_id]) ? $currencies[$currency_id]['code'] : null;
                        $line[] = $project['budget'];
                        $line[] = $project['cost_so_far'];
                    }

                    $line[] = $project['project_number'];
                    $line[] = $project['permalink'];

                    $this->exportWriteLine($line);
                }
            }
        }
    }

    public function canBeGroupedBy(): array
    {
        return [
            self::GROUP_BY_STATUS,
            self::GROUP_BY_CLIENT,
            self::GROUP_BY_CATEGORY,
            self::GROUP_BY_LABEL,
            self::GROUP_BY_LEADER,
            self::GROUP_BY_CREATED_ON,
            self::GROUP_BY_COMPLETED_ON,
        ];
    }

    // ---------------------------------------------------
    //  Client filter
    // ---------------------------------------------------

    public function getGroupingMaxLevel(): int
    {
        return 2;
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
            case 'client_filter':
                if (str_starts_with((string) $value, self::CLIENT_FILTER_SELECTED)) {
                    $this->filterByClientId($this->getIdFromFilterValue($value));
                } else {
                    $this->setClientFilter($value);
                }

                break;
            case 'category_filter':
                if (str_starts_with((string) $value, self::CATEGORY_FILTER_SELECTED)) {
                    $this->filterByCategoryNames($this->getNamesFromFilterValue($value, self::CATEGORY_FILTER_SELECTED));
                } elseif (str_starts_with((string) $value, self::CATEGORY_FILTER_NOT_SELECTED)) {
                    $this->filterByCategoryNames($this->getNamesFromFilterValue($value, self::CATEGORY_FILTER_NOT_SELECTED), true);
                } else {
                    $this->setCategoryFilter($value);
                }

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
            case 'lead_by_filter':
                $this->setUserFilterAttribute('lead', $value);

                break;
            case 'created_on_filter':
                $this->setDateFilterAttribute('created', $value);

                break;
            case 'completed_on_filter':
                $this->setDateFilterAttribute('completed', $value);

                break;
            default:
                parent::setAttribute($attribute, $value);
        }
    }

    /**
     * Set filter to filter records by $client_id.
     *
     * @param  int $client_id
     * @return int
     */
    public function filterByClientId($client_id)
    {
        $this->setClientFilter(self::CLIENT_FILTER_SELECTED);
        $this->setAdditionalProperty('client_id', (int) $client_id);
    }

    /**
     * Set client filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setClientFilter($value)
    {
        return $this->setAdditionalProperty('client_filter', $value);
    }

    // ---------------------------------------------------
    //  Category filter
    // ---------------------------------------------------

    /**
     * Filter projects by given list of categories.
     *
     * @param  array $category_names
     * @param  bool  $invert
     * @return array
     */
    public function filterByCategoryNames($category_names, $invert = false)
    {
        if ($invert) {
            $this->setCategoryFilter(self::CATEGORY_FILTER_NOT_SELECTED);
        } else {
            $this->setCategoryFilter(self::CATEGORY_FILTER_SELECTED);
        }

        $this->setAdditionalProperty('category_names', $category_names);
    }

    /**
     * Set category filter.
     *
     * @param  string $value
     * @return string
     */
    public function setCategoryFilter($value)
    {
        return $this->setAdditionalProperty('category_filter', $value);
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

    // ---------------------------------------------------
    //  Label filter
    // ---------------------------------------------------

    /**
     * Return lead by filter value.
     *
     * @return string
     */
    public function getLeadByFilter()
    {
        return $this->getAdditionalProperty('lead_by_filter', self::USER_FILTER_ANYBODY);
    }

    /**
     * Set filter by company values.
     *
     * @param int $company_id
     */
    public function filterLeadByCompany($company_id)
    {
        $this->setLeadByFilter(self::USER_FILTER_COMPANY_MEMBER);

        $this->setAdditionalProperty('lead_by_company_id', $company_id);
    }

    /**
     * Set lead by filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setLeadByFilter($value)
    {
        return $this->setAdditionalProperty('lead_by_filter', $value);
    }

    /**
     * Set lead by company value.
     *
     * @param $value
     */
    public function leadByCompanyMember($value)
    {
        $this->setLeadByFilter(self::USER_FILTER_COMPANY_MEMBER);
        $this->setAdditionalProperty('lead_by_company_id', $value);
    }

    /**
     * Return company ID set for user filter.
     *
     * @return array
     */
    public function getLeadByCompanyMember()
    {
        return $this->getAdditionalProperty('lead_by_company_id');
    }

    // ---------------------------------------------------
    //  Created on filter
    // ---------------------------------------------------

    /**
     * Set user filter to filter only tracked object for selected users.
     *
     * $user_ids can be an array of user ID-s or a single user ID or NULL
     *
     * @param array $user_ids
     */
    public function leadByUsers($user_ids)
    {
        $this->setLeadByFilter(self::USER_FILTER_SELECTED);

        if (is_array($user_ids)) {
            foreach ($user_ids as $k => $v) {
                $user_ids[$k] = (int) $v;
            }
        } else {
            if ($user_ids) {
                $user_ids = [$user_ids];
            } else {
                $user_ids = null;
            }
        }

        $this->setAdditionalProperty('lead_by_users', $user_ids);
    }

    /**
     * Return array of selected users.
     *
     * @return array
     */
    public function getLeadByUsers()
    {
        return $this->getAdditionalProperty('lead_by_users');
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
        if ($filter == DataFilter::DATE_FILTER_AGE_IS || DataFilter::DATE_FILTER_AGE_IS_LESS_THAN || $filter == DataFilter::DATE_FILTER_AGE_IS_MORE_THAN) {
            $this->setCreatedOnFilter($filter);
        } else {
            throw new InvalidParamError('filter', $filter);
        }

        return $this->setAdditionalProperty('created_age', (int) $value);
    }

    /**
     * Filter projects that are created on a given date.
     *
     * @param string $date
     */
    public function createdOnDate($date)
    {
        $this->setCreatedOnFilter(self::DATE_FILTER_SELECTED_DATE);
        $this->setAdditionalProperty('created_filter_on', (string) $date);
    }

    /**
     * Set created on filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setCreatedOnFilter($value)
    {
        return $this->setAdditionalProperty('created_on_filter', $value);
    }

    /**
     * Created before a given date (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function createdBeforeDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE : self::DATE_FILTER_BEFORE_SELECTED_DATE;
        $this->setCreatedOnFilter($filter);
        $this->setAdditionalProperty('created_filter_on', (string) $date);
    }

    /**
     * Created after a given date (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function createdAfterDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_AFTER_AND_ON_SELECTED_DATE : self::DATE_FILTER_AFTER_SELECTED_DATE;
        $this->setCreatedOnFilter($filter);
        $this->setAdditionalProperty('created_filter_on', (string) $date);
    }

    /**
     * Return created on filter value.
     *
     * @return DateValue
     */
    public function getCreatedOnDate()
    {
        $on = $this->getAdditionalProperty('created_filter_on');

        return $on ? new DateValue($on) : null;
    }

    // ---------------------------------------------------
    //  Completed on filter
    // ---------------------------------------------------

    /**
     * Return assignments filter on a given range.
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
     * Return value of created filter.
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

    // ---------------------------------------------------
    //  Include all projects
    // ---------------------------------------------------

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
     * Set include budget data flag.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIncludeBudgetData($value)
    {
        return $this->setAdditionalProperty('include_budget_data', (bool) $value);
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $this->describeUserFilter('lead', $result);

        if ($result['lead_by_filter'] === self::USER_FILTER_COMPANY_MEMBER) {
            $result['lead_by_company_member_id'] = $this->getLeadByCompanyMember();
        } elseif ($result['lead_by_filter'] === self::USER_FILTER_SELECTED) {
            $result['lead_by_user_ids'] = $this->getLeadByUsers();
        }

        $this->describeDateFilter('created', $result);
        $this->describeDateFilter('completed', $result);

        // Client filter
        $result['client_filter'] = $this->getClientFilter();
        $result['client_id'] = $this->getClientId();

        // Category filter
        $result['category_filter'] = $this->getCategoryFilter();
        $result['category_names'] = $this->getCategoryNames();

        if (is_array($result['category_names'])) {
            sort($result['category_names']);
        } else {
            $result['category_names'] = [];
        }

        // Label filter
        $result['label_filter'] = $this->getLabelFilter();
        $result['label_names'] = $this->getLabelNames();

        if (is_array($result['label_names'])) {
            sort($result['label_names']);
        } else {
            $result['label_names'] = [];
        }

        $result['include_all_projects'] = $this->getIncludeAllProjects();
        $result['include_budget_data'] = $this->getIncludeBudgetData();

        return $result;
    }

    public function canRun(User $user): bool
    {
        return $user->isPowerUser();
    }
}
