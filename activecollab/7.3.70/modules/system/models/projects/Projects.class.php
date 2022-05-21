<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Collections\ActivityLog\RangeActivityLogsInCursorCollection;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents\ProjectBudgetChangedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents\ProjectCompletedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents\ProjectCreatedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents\ProjectReopenedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents\ProjectUpdatedEvent;

class Projects extends BaseProjects
{
    public const PROJECTS_PER_PAGE = 100;

    public const PROJECT_FILTER_ANY = 'any';
    public const PROJECT_FILTER_ACTIVE = 'active';
    public const PROJECT_FILTER_COMPLETED = 'completed';
    public const PROJECT_FILTER_CATEGORY = 'category';
    public const PROJECT_FILTER_CLIENT = 'client';
    public const PROJECT_FILTER_SELECTED = 'selected';
    public const PROJECT_FILTER_TIME_AND_EXPENSES_ANY = 'time-and-expenses-any';
    public const PROJECT_FILTER_TIME_AND_EXPENSES_ACTIVE = 'time-and-expenses-active';

    public static function getAvailableProjectElementClasses(): array
    {
        return [
            TaskList::class,
            Task::class,
            Discussion::class,
            LocalFile::class,
            WarehouseFile::class,
            GoogleDriveFile::class,
            DropboxFile::class,
            Note::class,
            RecurringTask::class,
        ];
    }

    /**
     * Return new collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'activity_logs_in_project')) {
            return self::prepareActivityLogsInProjectCollection($collection_name, $user);
        }  elseif (str_starts_with($collection_name, 'for_screen')) {
            return self::prepareProjectsRawCollection($collection_name, $user);
        } elseif (str_starts_with($collection_name, 'range_activity_logs_in_project')) {
            return self::prepareRangeActivityLogsInProjectCollection($collection_name, $user);
        }  elseif (str_starts_with($collection_name, 'cursor_range_activity_logs_in_project')) {
            return self::prepareRangeActivityLogsInProjectCursorCollection($collection_name, $user);
        } elseif (str_starts_with($collection_name, 'get_filters_for_archived_projects')) {
            return self::prepareArchivedProjectFilters($collection_name, $user);
        } else {
            if (str_starts_with($collection_name, 'project_budget')) {
                return self::prepareProjectBudgetCollection($collection_name, $user);
            } elseif (str_starts_with($collection_name, 'project_additional_data')) {
                return self::prepareProjectAdditionalDataCollection($collection_name, $user);
            } elseif (str_starts_with($collection_name, 'financial_stats')) {
                return self::prepareFinancialStatsCollection($collection_name, $user);
            } elseif (str_starts_with($collection_name, 'projects_invoicing_data')) {
                return self::prepareProjectsInvoicingDataCollection($collection_name, $user);
            } else {
                $collection = parent::prepareCollection($collection_name, $user);

                $collection->setPreExecuteCallback(function ($ids) {
                    self::preloadProjectElementCounts($ids);
                });

                if (!$user->isOwner()) {
                    $collection->setJoinTable('project_users');
                }

                // Active projects (sorted by last update or by name)
                if (str_starts_with($collection_name, 'active_projects')) {
                    self::prepareActiveProjectsCollection($collection, $collection_name, $user);
                } elseif (str_starts_with($collection_name, 'workload_projects')) {
                    self::prepareWorkloadProjectsCollection($collection, $collection_name, $user);
                // Filtered projects
                } else {
                    if (str_starts_with($collection_name, 'filtered_projects')) {
                        self::prepareFilterProjectsCollection($collection, $collection_name, $user);

                    // Archived projects
                    } else {
                        if (str_starts_with($collection_name, 'archived_projects')) {
                            self::prepareArchivedProjectCollection($collection, $collection_name, $user);

                            return $collection;
                        // Company projects
                        } else {
                            if (str_starts_with($collection_name, 'company_projects')) {
                                $bits = explode('_', $collection_name);

                                $page = array_pop($bits);
                                array_pop($bits); // _page_

                                if ($company = DataObjectPool::get('Company', array_pop($bits))) {
                                    $collection->setPagination($page, self::PROJECTS_PER_PAGE);
                                    $collection->setOrderBy('projects.updated_on DESC');

                                    if ($user->isOwner()) {
                                        $collection->setConditions('company_id = ? AND is_trashed = ?', $company->getId(), false);
                                    } else {
                                        $collection->setConditions('projects.company_id = ? AND projects.is_trashed = ? AND project_users.user_id = ?', $company->getId(), false, $user->getId());
                                    }
                                } else {
                                    throw new ImpossibleCollectionError();
                                }

                            // Active user projects
                            } else {
                                if (str_starts_with($collection_name, 'active_user_projects')) {
                                    self::prepareActiveUserProjectsCollection($collection, $collection_name, $user);
                                } else {
                                    throw new InvalidParamError('collection_name', $collection_name, 'Invalid collection name');
                                }
                            }
                        }
                    }
                }

                return $collection;
            }
        }
    }

    private static function prepareArchivedProjectCollection(ModelCollection &$collection, $collection_name, $user)
    {
        if ($user->isOwner()) {
            $conditions = [DB::prepare('is_trashed = ? AND completed_on IS NOT NULL', false)];
        } else {
            $conditions = [DB::prepare('projects.is_trashed = ? AND projects.completed_on IS NOT NULL AND project_users.user_id = ?', false, $user->getId())];
        }

        $bits = explode('_', $collection_name);

        $page = array_pop($bits);
        array_pop($bits);

        if ($page < 1) {
            $page = 1;
        }

        $leader_ids = array_pop($bits);

        if ($leader_ids !== 'any') {
            $leader_ids = explode(',', $leader_ids);
            $conditions[] = DB::prepare('(leader_id IN (?))', $leader_ids);
        }
        array_pop($bits); //_leaders

        $label_ids = array_pop($bits);
        if ($label_ids !== 'any') {
            $label_ids = explode(',', (string) $label_ids);
            $conditions[] = DB::prepare('(label_id IN (?))', $label_ids);
        }
        array_pop($bits); // _label_

        $company_ids = array_pop($bits);
        if ($company_ids !== 'any') {
            $company_ids = explode(',', (string) $company_ids);
            $conditions[] = DB::prepare('(company_id IN (?))', $company_ids);
        }
        array_pop($bits);

        $category_ids = array_pop($bits);
        if ($category_ids !== 'any') {
            $category_ids = explode(',', (string) $category_ids);
            $conditions[] = DB::prepare('(category_id IN (?))', $category_ids);
        }

        $collection->setConditions(implode(' AND ', $conditions));

        if (str_contains($collection_name, 'sort_by_size')) {
            self::prepareSortByArchivedProjectsCollection($collection);
        } else {
            $collection->setOrderBy('completed_on DESC');
        }

        $collection->setPagination((int) $page, 30);
    }

    /**
     * Prepare activity logs in collection error.
     *
     * @param  string                   $collection_name
     * @param  User|null                $user
     * @return ActivityLogsInCollection
     */
    private static function prepareActivityLogsInProjectCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        $page = array_pop($bits);
        array_pop($bits); // _page_

        /** @var Project $project */
        if ($project = DataObjectPool::get('Project', array_pop($bits))) {
            $collection = new ActivityLogsInCollection($collection_name, $user);

            $collection->setWhosAsking($user);
            $collection->setIn($project);
            $collection->setPagination($page, 50);

            return $collection;
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }
    }

    private static function prepareArchivedProjectFilters($collection_name, $user)
    {
        return (new ArchivedProjectFiltersCollection($collection_name))
            ->setWhosAsking($user);
    }

    private static function prepareProjectsRawCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);
        $projects_view = array_pop($bits);

        return (new ProjectsRawCollection($collection_name))
            ->setProjectsView($projects_view)
            ->setWhosAsking($user);
    }

    private static function prepareRangeActivityLogsInProjectCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        $page = array_pop($bits);
        array_pop($bits); // _page_
        $range_dates = array_pop($bits);

        /** @var Project $project */
        if ($project = DataObjectPool::get(Project::class, array_pop($bits))) {
            $collection = new RangeActivityLogsInCollection($collection_name);

            $collection
                ->setIn($project)
                ->setDates($range_dates)
                ->setWhosAsking($user)
                ->setPagination($page, 50);

            return $collection;
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }
    }

    private static function prepareRangeActivityLogsInProjectCursorCollection(
        $collection_name,
        $user
    ): RangeActivityLogsInCursorCollection
    {
        $bits = explode('_', $collection_name);

        $range_dates = array_pop($bits);

        /** @var Project $project */
        if ($project = DataObjectPool::get(Project::class, array_pop($bits))) {
            $collection = new RangeActivityLogsInCursorCollection($collection_name);

            $collection
                ->setIn($project)
                ->setDates($range_dates)
                ->setWhosAsking($user);

            return $collection;
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }
    }

    /**
     * Prepare activity logs in collection error.
     *
     * @param  string                  $collection_name
     * @param  User|null               $user
     * @return ProjectBudgetCollection
     */
    private static function prepareProjectBudgetCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        /** @var Project $project */
        if ($project = DataObjectPool::get(Project::class, array_pop($bits))) {
            return (new ProjectBudgetCollection($collection_name))->setProject($project)->setWhosAsking($user);
        } else {
            throw new ImpossibleCollectionError('Project not found');
        }
    }

    private static function prepareProjectAdditionalDataCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        /** @var Project $project */
        if ($project = DataObjectPool::get(Project::class, array_pop($bits))) {
            return (new ProjectAdditionalDataCollection($collection_name))->setProject($project)->setWhosAsking($user);
        } else {
            throw new ImpossibleCollectionError('Project not found');
        }
    }

    /**
     * Prepare active projects model collection.
     *
     * @param string $collection_name
     * @param User   $user
     */
    public static function prepareActiveProjectsCollection(ModelCollection &$collection, $collection_name, $user)
    {
        if ($user->isOwner()) {
            $collection->setConditions('is_trashed = ? AND completed_on IS NULL', false);
        } else {
            $collection->setConditions('projects.is_trashed = ? AND projects.completed_on IS NULL AND project_users.user_id = ?', false, $user->getId());
        }

        $bits = explode('_', $collection_name);
        $collection->setPagination(array_pop($bits), self::PROJECTS_PER_PAGE);

        if (str_starts_with($collection_name, 'active_projects_by_name')) {
            $order_projects_by = 'name';
            $favorite_project_ids = self::getFavoriteProjectIds($user, 'name DESC');
        } else {
            // Added ID in sort because of correct paging when there are more than 100 projects and multiple records
            // have the same updated_on values (e.g. when importing db)
            $order_projects_by = 'updated_on DESC, id DESC';
            $favorite_project_ids = self::getFavoriteProjectIds($user, 'updated_on DESC');
        }

        if (empty($favorite_project_ids)) {
            $collection->setOrderBy("projects.$order_projects_by");
        } else {
            $collection->setOrderBy(DB::prepare("FIELD(projects.id, ?) DESC, projects.$order_projects_by", $favorite_project_ids)); // Put favorite projects on top of the list
        }
    }

    private static function prepareWorkloadProjectsCollection(ModelCollection &$collection, $collection_name, $user)
    {
        if ($user->isOwner()) {
            $collection->setConditions(
                'is_trashed = ? AND is_sample = ?',
                false,
                false,
            );
        } else {
            $collection->setConditions(
                'projects.is_trashed = ? AND projects.is_sample = ? AND project_users.user_id = ?',
                false,
                false,
                $user->getId(),
            );
        }
    }

    private static function prepareFinancialStatsCollection(string $collection_name, User $user)
    {
        $bits = explode('_', $collection_name);

        /** @var Project $project */
        if ($project = DataObjectPool::get(Project::class, array_pop($bits))) {
            return (new ProjectFinancialStatsCollection($collection_name))->setProject($project);
        } else {
            throw new ImpossibleCollectionError('Project not found');
        }
    }

    private static function prepareProjectsInvoicingDataCollection(string $collection_name, User $user)
    {
        return new ProjectsInvoicingDataCollection($collection_name);
    }

    /**
     * Prepare filtered projects model collection.
     *
     * @param string $collection_name
     * @param User   $user
     */
    public static function prepareFilterProjectsCollection(ModelCollection &$collection, $collection_name, $user)
    {
        if ($user->isOwner()) {
            $conditions = [DB::prepare('(is_trashed = 0 AND completed_on IS NULL)')];
        } else {
            $conditions = [DB::prepare('(projects.is_trashed = 0 AND projects.completed_on IS NULL AND project_users.user_id = ?)', $user->getId())];
        }

        $bits = explode('_', $collection_name);

        $page = array_pop($bits); // get the number
        array_pop($bits); // remove _page_

        if ($page < 1) {
            $page = 1;
        }

        $category_id = array_pop($bits); // get the number
        array_pop($bits); // remove _category_

        $label_id = array_pop($bits); // get the number
        array_pop($bits); // remove _label_

        $client_id = array_pop($bits); // get the number
        array_pop($bits); // remove _client_

        if ($category_id != 'any') {
            $conditions[] = DB::prepare('(category_id = ?)', $category_id);
        }

        if ($label_id != 'any') {
            $conditions[] = DB::prepare('(label_id = ?)', $label_id);
        }

        if ($client_id != 'any') {
            $conditions[] = DB::prepare('(company_id = ?)', $client_id);
        }

        $collection->setConditions(implode(' AND ', $conditions));
        $collection->setPagination($page, self::PROJECTS_PER_PAGE);

        if (str_starts_with($collection_name, 'filtered_projects_by_name')) {
            $order_projects_by = 'name';
            $favorite_project_ids = self::getFavoriteProjectIds($user, 'name DESC');
        } else {
            // Added ID in sort because of correct paging when there are more than 100 projects and multiple records
            // have the same last_activity_on values (e.g. when importing db)
            $order_projects_by = 'last_activity_on DESC, id DESC';
            $favorite_project_ids = self::getFavoriteProjectIds($user, 'last_activity_on ASC');
        }

        if (empty($favorite_project_ids)) {
            $collection->setOrderBy("projects.$order_projects_by");
        } else {
            $collection->setOrderBy(DB::prepare("FIELD(projects.id, ?) DESC, projects.$order_projects_by", $favorite_project_ids)); // Put favorite projects on top of the list
        }
    }

    /**
     * Return favorite project ID-s for the given user.
     *
     * @param  string $sort_by
     * @return int[]
     */
    private static function getFavoriteProjectIds(User $user, $sort_by = 'name')
    {
        if ($favorite_project_ids = DB::executeFirstColumn("SELECT f.parent_id AS 'project_id' FROM favorites AS f LEFT JOIN projects AS p ON f.parent_type = 'Project' AND f.parent_id = p.id WHERE f.user_id = ? AND p.is_trashed = ? ORDER BY p.$sort_by", $user->getId(), false)) {
            return $favorite_project_ids;
        } else {
            return [];
        }
    }

    /**
     * Prepare users active projects collection.
     *
     * @param string $collection_name
     * @param User   $user
     */
    private static function prepareActiveUserProjectsCollection(ModelCollection &$collection, $collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        $page = array_pop($bits);
        array_pop($bits); // _page_

        if ($targed_user = DataObjectPool::get('User', array_pop($bits))) {
            $collection->setJoinTable('project_users');

            if ($user->isOwner()) {
                $collection->setConditions('projects.is_trashed = ? AND projects.completed_on IS NULL AND project_users.user_id = ?', false, $targed_user->getId());
            } else {
                if ($possible_project_ids = $possible_project_ids = self::findIdsByUser($user, false, DB::prepare('is_trashed = ? AND completed_on IS NULL', false))) {
                    $collection->setConditions('projects.id IN (?) AND projects.is_trashed = ? AND projects.completed_on IS NULL AND project_users.user_id = ?', $possible_project_ids, false, $targed_user->getId());
                } else {
                    throw new ImpossibleCollectionError();
                }
            }

            $collection->setPagination($page, self::PROJECTS_PER_PAGE);
            $collection->setOrderBy('projects.updated_on DESC');
        } else {
            throw new ImpossibleCollectionError();
        }
    }

    /**
     * Return project ID-s by conditions.
     *
     * @param  IUser|User        $user
     * @param  bool              $all_for_owners
     * @param  string|array|null $additional_conditions
     * @return array
     */
    public static function findIdsByUser(IUser $user, $all_for_owners = false, $additional_conditions = null)
    {
        if ($additional_conditions) {
            $additional_conditions = DB::prepareConditions($additional_conditions);
        }

        if ($all_for_owners && $user->isFinancialManager()) {
            $conditions = $additional_conditions ? "WHERE $additional_conditions" : '';

            return DB::executeFirstColumn("SELECT id FROM projects $conditions ORDER BY name");
        }

        $conditions = [DB::prepare('project_users.user_id = ? AND project_users.project_id = projects.id', $user->getId())];
        if ($additional_conditions) {
            $conditions[] = "($additional_conditions)";
        }

        return DB::executeFirstColumn('SELECT projects.id FROM projects, project_users WHERE ' . implode(' AND ', $conditions) . ' ORDER BY projects.name');
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Project
    {
        $send_invitations = array_var($attributes, 'send_invitations', true, true);

        try {
            DB::beginWork('Creating project @ ' . __CLASS__);

            self::prepareBasedOnForProjectCreation($attributes);

            $template_date_reference = self::prepareTemplateDateReference($attributes);
            $template = self::prepareTemplateForProjectCreation($attributes);

            $project = parent::create($attributes, $save, false);

            if ($project->isLoaded()) {
                if (empty($attributes['skip_default_task_list'])) {
                    TaskLists::create(
                        [
                            'name' => ConfigOptions::getValue('default_task_list_name'),
                            'project_id' => $project->getId(),
                        ],
                        true,
                        false,
                    );
                }

                if (array_key_exists('skip_default_task_list', $attributes)) {
                    unset($attributes['skip_default_task_list']);
                }

                $members_to_add = [
                    $project->getCreatedBy(),
                ];

                $project_leader = $project->getLeader();

                if ($project_leader instanceof User && $project_leader->getId() != $project->getCreatedById()) {
                    $members_to_add[] = $project_leader;
                }

                // Send invitiation only if there is leader set, and leader is not the person who creates a project
                $project->addMembers(
                    $members_to_add,
                    [
                        'send_invitations' => $send_invitations && count($members_to_add) > 1,
                    ],
                );

                $project->tryToAddMembersFrom(
                    $attributes,
                    'members',
                    [
                        'send_invitations' => $send_invitations,
                    ],
                );

                if (array_key_exists('hourly_rates', $attributes)) {
                    $project->setHourlyRates($attributes['hourly_rates']);
                }

                if ($template) {
                    $project->applyTemplate(
                        $template,
                        AngieApplication::authentication()->getLoggedUser(),
                        true,
                        $template_date_reference,
                    );
                }
            }

            DB::commit('Project created @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to create project instance');

            throw $e;
        }

        if ($announce) {
            DataObjectPool::announce(new ProjectCreatedEvent($project));
        }

        return $project;
    }

    // ---------------------------------------------------
    //  Finders
    // ---------------------------------------------------

    private static function prepareBasedOnForProjectCreation(array &$attributes): void
    {
        if (isset($attributes['estimate_id']) && $attributes['estimate_id']) {
            $estimate = DataObjectPool::get(Estimate::class, $attributes['estimate_id']);

            if ($estimate instanceof Estimate) {
                $attributes['based_on_type'] = Estimate::class;
                $attributes['based_on_id'] = $estimate->getId();
            }

            unset($attributes['estimate_id']);
        }
    }

    private static function prepareTemplateDateReference(array &$attributes): DateValue
    {
        $reference = null;

        if (array_key_exists('template_date_reference', $attributes)) {
            if (!empty($attributes['template_date_reference'])) {
                $reference = DateValue::makeFromString($attributes['template_date_reference']);
            }

            unset($attributes['template_date_reference']);
        }

        return $reference ?? DateValue::now();
    }

    private static function prepareTemplateForProjectCreation(array &$attributes): ?ProjectTemplate
    {
        $template = null;

        if (array_key_exists('template_id', $attributes)) {
            if (!empty($attributes['template_id'])) {
                $template = DataObjectPool::get(
                    ProjectTemplate::class,
                    $attributes['template_id'],
                );
            }
        }

        if (!$template instanceof ProjectTemplate) {
            unset($attributes['template_id']);
        }

        return $template;
    }

    /**
     * Add user to many projects.
     *
     * @return array
     */
    public static function addUserToManyProjects(User $by, User $user, array $project_ids)
    {
        if (!empty($project_ids)) {
            if ($projects = self::findByIds($project_ids)) {
                foreach ($projects as $project) {
                    if ($project->canManagePeople($by)) {
                        $project->addMembers([$user]);
                    }
                }
            }
        }

        return $user->getProjectIds();
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): Project
    {
        if ($instance instanceof Project) {
            try {
                DB::beginWork('Begin: updating project @ ' . __CLASS__);

                parent::update($instance, $attributes, $save);

                if ($save && array_key_exists('hourly_rates', $attributes)) {
                    $instance->setHourlyRates($attributes['hourly_rates']);
                }

                DB::commit('Done: updating project @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Rollback: updating project @ ' . __CLASS__);

                throw $e;
            }
        } else {
            throw new InvalidInstanceError('instance', $instance, 'Project');
        }

        if ($save) {
            self::announceProjectUpdate($instance, $attributes);
        }

        return $instance;
    }

    private static function announceProjectUpdate(Project $project, array $attributes): void
    {
        if (self::isProjectCompletedStateChanged($attributes)) {
            if ($project->isCompleted()) {
                DataObjectPool::announce(new ProjectCompletedEvent($project));
            } else {
                DataObjectPool::announce(new ProjectReopenedEvent($project));
            }
        } elseif (self::isProjectBudgetChanged($attributes)) {
            DataObjectPool::announce(new ProjectBudgetChangedEvent($project));
        } else {
            DataObjectPool::announce(new ProjectUpdatedEvent($project));
        }
    }

    private static function isProjectCompletedStateChanged(array $attributes): bool
    {
        foreach (['completed_by_id', 'completed_by_name', 'completed_by_email', 'completed_on'] as $attribute) {
            if (array_key_exists($attribute, $attributes)) {
                return true;
            }
        }

        return false;
    }

    private static function isProjectBudgetChanged(array $attributes): bool
    {
        return array_key_exists('budget', $attributes);
    }

    /**
     * Rebuild updated activities.
     */
    public static function rebuildUpdateActivites()
    {
        $modifications = DB::execute(
            'SELECT DISTINCT l.id, l.parent_id, l.created_on, l.created_by_id, l.created_by_name, l.created_by_email
                FROM modification_logs AS l LEFT JOIN modification_log_values AS lv ON l.id = lv.modification_id
                WHERE l.parent_type = ? AND lv.field IN (?)',
            Project::class,
            self::whatIsWorthRemembering(),
        );

        if ($modifications) {
            $ids = $modification_ids = [];

            foreach ($modifications as $modification) {
                $modification_ids[] = $modification['id'];

                if (!in_array($modification['parent_id'], $ids)) {
                    $ids[] = $modification['parent_id'];
                }
            }

            $object_modifications = ActivityLogs::prepareFieldValuesForSerialization(
                $modification_ids,
                self::whatIsWorthRemembering(),
            );

            $batch = new DBBatchInsert(
                'activity_logs',
                [
                    'type',
                    'parent_type',
                    'parent_id',
                    'parent_path',
                    'created_on',
                    'created_by_id',
                    'created_by_name',
                    'created_by_email',
                    'raw_additional_properties',
                ],
            );

            foreach ($modifications as $modification) {
                $batch->insertArray(
                    [
                        'type' => 'InstanceUpdatedActivityLog',
                        'parent_type' => 'Project',
                        'parent_id' => $modification['parent_id'],
                        'parent_path' => 'projects/' . $modification['parent_id'],
                        'created_on' => $modification['created_on'],
                        'created_by_id' => $modification['created_by_id'],
                        'created_by_name' => $modification['created_by_name'],
                        'created_by_email' => $modification['created_by_email'],
                        'raw_additional_properties' => serialize(['modifications' => $object_modifications[$modification['id']]]),
                    ],
                );
            }

            $batch->done();
        }
    }

    public static function whatIsWorthRemembering(): array
    {
        return [
            'name',
            'completed_on',
            'is_trashed',
        ];
    }

    public static function canAdd(User $user): bool
    {
        return $user->isPowerUser();
    }

    /**
     * Return all projects that $user is involved with.
     *
     * @param  bool      $all_for_admins_and_pms
     * @param  string    $additional_conditions
     * @param  string    $order_by
     * @return Project[]
     */
    public static function findByUser(User $user, $all_for_admins_and_pms = false, $additional_conditions = null, $order_by = null)
    {
        return self::_findByUser($user, $all_for_admins_and_pms, $additional_conditions, $order_by);
    }

    /**
     * Return projects that $user belongs to.
     *
     * @param  bool      $all_for_admins_and_pms
     * @param  mixed     $additional_conditions
     * @param  string    $order_by
     * @return Project[]
     */
    private static function _findByUser(User $user, $all_for_admins_and_pms = false, $additional_conditions = null, $order_by = null)
    {
        if ($additional_conditions) {
            $additional_conditions = '(' . DB::prepareConditions($additional_conditions) . ')';
        }

        if (empty($order_by)) {
            $order_by = 'projects.name';
        }

        if ($all_for_admins_and_pms && $user->isPowerUser()) {
            if ($additional_conditions) {
                return self::findBySQL("SELECT * FROM projects WHERE $additional_conditions ORDER BY $order_by");
            } else {
                return self::findBySQL("SELECT * FROM projects ORDER BY $order_by");
            }
        } else {
            if ($additional_conditions) {
                return self::findBySQL("SELECT projects.* FROM projects, project_users WHERE project_users.user_id = ? AND project_users.project_id = projects.id AND $additional_conditions ORDER BY $order_by", $user->getId());
            } else {
                return self::findBySQL("SELECT projects.* FROM projects, project_users WHERE project_users.user_id = ? AND project_users.project_id = projects.id ORDER BY $order_by", $user->getId());
            }
        }
    }

    /**
     * Return active projects that $user is involved with.
     *
     * @param  bool      $all_for_admins_and_pms
     * @return Project[]
     */
    public static function findActiveByUser(User $user, $all_for_admins_and_pms = false)
    {
        return self::_findByUser($user, $all_for_admins_and_pms, ['projects.state >= ? AND projects.completed_on IS NULL', STATE_VISIBLE]);
    }

    /**
     * Return completed projects that $user is involved with.
     *
     * @param  bool      $all_for_admins_and_pms
     * @return Project[]
     */
    public static function findCompletedByUser(User $user, $all_for_admins_and_pms = false)
    {
        return self::_findByUser(
            $user,
            $all_for_admins_and_pms,
            [
                'projects.state >= ? AND projects.completed_on IS NOT NULL',
                STATE_VISIBLE,
            ],
            'completed_on DESC',
        );
    }

    /**
     * Find active projects that have budget property set.
     *
     * @param  bool      $all_for_admins_and_pms
     * @return Project[]
     */
    public static function findActiveByUserWithBudget(User $user, $all_for_admins_and_pms = false)
    {
        return self::_findByUser(
            $user,
            $all_for_admins_and_pms,
            [
                'projects.state >= ? AND projects.completed_on IS NULL AND projects.budget > 0',
                STATE_VISIBLE,
            ],
        );
    }

    /**
     * Return projects that given user can invite people on.
     */
    public static function findWhereUserCanInvitePeople(User $user): array
    {
        if ($user->isOwner()) {
            $result = DB::execute('SELECT id, name FROM projects WHERE is_trashed = 0 AND completed_on IS NULL ');
        } elseif ($user->isPowerUser()) {
            $result = DB::execute(
                'SELECT id, name FROM projects WHERE is_trashed = 0 AND completed_on IS NULL AND (leader_id = ? OR created_by_id = ?)',
                $user->getId(),
                $user->getId(),
            );
        } else {
            $result = DB::execute(
                'SELECT id, name FROM projects WHERE is_trashed = 0 AND completed_on IS NULL AND leader_id = ?',
                $user->getId(),
            );
        }

        return $result instanceof DBResult
            ? $result->toMap('id', 'name')
            : [];
    }

    /**
     * Return number of projects that use given currency.
     *
     * @return int
     */
    public static function countByCurrency(Currency $currency)
    {
        if ($currency->getIsDefault()) {
            return self::count(['currency_id IS NULL OR currency_id = ?', $currency->getId()]);
        } else {
            return self::count(['currency_id = ?', $currency->getId()]);
        }
    }

    public static function getIdNameMapByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $result = [];

        $rows = DB::execute('SELECT id, name FROM projects WHERE id IN (?) ORDER BY name', $ids);

        if ($rows) {
            foreach ($rows as $row) {
                $result[$row['id']] = $row['name'];
            }
        }

        return $result;
    }

    /**
     * Return projects ID name map for the given user and conditions.
     *
     * @param  string|array|null $additional_conditions
     * @param  bool              $include_completed
     * @return array
     */
    public static function getIdNameMapFor(User $user, $additional_conditions = null, $include_completed = false)
    {
        $result = [];

        $additional_conditions = $additional_conditions ? ' AND (' . DB::prepareConditions($additional_conditions) . ')' : '';

        if (!$include_completed) {
            $additional_conditions .= ' AND projects.completed_on IS NULL';
        }

        if ($user->isOwner()) {
            $rows = DB::execute("SELECT id, name FROM projects WHERE is_trashed = ? $additional_conditions ORDER BY id", false);
        } else {
            $rows = DB::execute("SELECT projects.id, projects.name FROM projects LEFT JOIN project_users ON projects.id = project_users.project_id WHERE projects.is_trashed = ? AND project_users.user_id = ? $additional_conditions ORDER BY projects.id", false, $user->getId());
        }

        if ($rows) {
            foreach ($rows as $row) {
                $result[$row['id']] = $row['name'];
            }
        }

        return $result;
    }

    /**
     * Return ID Details map.
     *
     * @param  array $ids
     * @param  mixed $additional_conditions
     * @return array
     */
    public static function getIdNameMap($ids = null, $additional_conditions = null)
    {
        $conditions = [];

        if ($ids) {
            $conditions[] = DB::prepare('(id IN (?))', $ids);
        }

        if ($additional_conditions) {
            $conditions[] = '(' . DB::prepareConditions($additional_conditions) . ')';
        }

        if ($rows = DB::execute('SELECT id, name FROM projects ' . (count($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '') . ' ORDER BY id')) {
            $result = [];

            foreach ($rows as $row) {
                $result[$row['id']] = $row['name'];
            }

            return $result;
        }

        return null;
    }

    /**
     * Return project ID-s based on project filter and given user.
     *
     * @param  string|null $additional_conditions
     * @return array
     */
    public static function getProjectIdsByDataFilter(
        DataFilter $filter,
        User $user,
        $additional_conditions = null
    )
    {
        if (
            !method_exists($filter, 'getProjectFilter')
            || !method_exists($filter, 'getIncludeAllProjects')
        ) {
            throw new InvalidInstanceError(
                'filter',
                $filter,
                DataFilter::class,
                '$filter is required to be DataFilter instance with getProjectFilter() and getIncludeAllProjects() methods defined',
            );
        }

        $include_all_projects = $filter->getIncludeAllProjects();

        if ($additional_conditions) {
            $additional_conditions = DB::prepare("(projects.is_trashed = ?) AND ($additional_conditions)", false);
        } else {
            $additional_conditions = DB::prepare('projects.is_trashed = ?', false);
        }

        switch ($filter->getProjectFilter()) {
            // Go through all projects
            case self::PROJECT_FILTER_ANY:
                $project_ids = self::findIdsByUser($user, $include_all_projects, $additional_conditions);

                if (empty($project_ids)) {
                    throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_ANY, null, 'There are no projects in the database that current user can see');
                }

                break;

            // Go only through active projects
            case self::PROJECT_FILTER_ACTIVE:
                $project_ids = self::findIdsByUser($user, $include_all_projects, "(projects.completed_on IS NULL) AND ($additional_conditions)");

                if (empty($project_ids)) {
                    throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_ACTIVE, null, 'There are no active projects in the database that current user can see');
                }

                break;

            // Go through completed projects
            case self::PROJECT_FILTER_COMPLETED:
                $project_ids = self::findIdsByUser($user, $include_all_projects, "(projects.completed_on IS NOT NULL) AND ($additional_conditions)");

                if (empty($project_ids)) {
                    throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_COMPLETED, null, 'There are no completed projects in the database that current user can see');
                }

                break;

            // Filter by project client
            case self::PROJECT_FILTER_CLIENT:
                $project_client_id = $filter->getProjectClientId();

                if ($project_client_id) {
                    $project_ids = self::findIdsByUser($user, $include_all_projects, DB::prepare("(projects.company_id = ?) AND ($additional_conditions)", $project_client_id));

                    if (empty($project_ids)) {
                        throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_CLIENT, $project_client_id, 'There are no projects for this client or user cant see any of the projects for this client');
                    }
                } else {
                    throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_CLIENT, $project_client_id, 'Project client not selected');
                }

                break;

            // Filter by selected project category
            case self::PROJECT_FILTER_CATEGORY:
                $project_categories = Categories::getIdNameMap(null, ProjectCategory::class);

                if ($project_categories) {
                    $project_category_id = $filter->getProjectCategoryId();

                    if ($project_category_id) {
                        $project_ids = self::findIdsByUser($user, $include_all_projects, DB::prepare("(projects.category_id = ?) AND ($additional_conditions)", $project_category_id));

                        if (empty($project_ids)) {
                            throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_CATEGORY, $project_category_id, 'Category is empty or user cant see any of the projects in it');
                        }
                    } else {
                        throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_CATEGORY, $project_category_id, 'Project category not selected');
                    }
                } else {
                    throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_CATEGORY, $filter->getProjectCategoryId(), 'There are no project categories defined in the database');
                }

                break;

            // Filter by list of selected projects
            case self::PROJECT_FILTER_SELECTED:
                $selected_project_ids = $filter->getProjectIds();

                if (is_array($selected_project_ids) && count($selected_project_ids)) {
                    $project_ids = self::findIdsByUser($user, $include_all_projects, DB::prepare("(projects.id IN (?)) AND ($additional_conditions)", $selected_project_ids));

                    if (empty($project_ids)) {
                        throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_SELECTED, $selected_project_ids, "User can't access any of the selected projects");
                    }
                } else {
                    throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_SELECTED, $selected_project_ids, 'Selected project IDs array is empty');
                }

                break;

            // Filter by budget type -> time and expenses only (all)
            case self::PROJECT_FILTER_TIME_AND_EXPENSES_ANY:
                $time_and_expenses = sprintf('projects.budget_type = "%s"', Project::BUDGET_TYPE_PAY_AS_YOU_GO);
                $project_ids = self::findIdsByUser($user, $include_all_projects, "($time_and_expenses) AND ($additional_conditions)");

                if (empty($project_ids)) {
                    throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_TIME_AND_EXPENSES_ANY, null, 'There are no projects with time and expenses budget type in the database that current user can see');
                }

                break;

            // Filter by budget type -> time and expenses only (active)
            case self::PROJECT_FILTER_TIME_AND_EXPENSES_ACTIVE:
                $time_and_expenses = sprintf('projects.budget_type = "%s"', Project::BUDGET_TYPE_PAY_AS_YOU_GO);
                $project_ids = self::findIdsByUser($user, $include_all_projects, "(projects.completed_on IS NULL) AND ($time_and_expenses) AND ($additional_conditions)");

                if (empty($project_ids)) {
                    throw new DataFilterConditionsError('project_filter', self::PROJECT_FILTER_TIME_AND_EXPENSES_ACTIVE, null, 'There are no active projects with time and expenses budget type in the database that current user can see');
                }

                break;

            // Invalid filter value
            default:
                throw new DataFilterConditionsError('project_filter', $filter->getProjectFilter(), 'mixed', 'Unknown project filter');
        }

        return $project_ids;
    }

    public static function preloadProjectElementCounts(array $project_ids): void
    {
        Discussions::preloadCountByProject($project_ids);
        Files::preloadCountByProject($project_ids);
        Tasks::preloadCountByProject($project_ids);
        TaskLists::preloadCountByProject($project_ids);
        Notes::preloadCountByProject($project_ids);
        Users::preloadMemberIdsFromConnectionTable(
            Project::class,
            $project_ids,
            'project_users',
            'project_id',
            'user_id',
            false,
        );
        Files::preloadFileSizeSumByProject($project_ids);
    }

    public static function preloadProjectElementsCount(
        array $project_ids,
        string $elements_table_name
    ): array
    {
        $result = [];

        $rows = DB::execute(
            sprintf(
                "SELECT `project_id`, COUNT('id') AS 'row_count' FROM %s WHERE `is_trashed` = ? AND `project_id` IN (?) GROUP BY `project_id`",
                $elements_table_name,
            ),
            false,
            $project_ids,
        );

        if ($rows) {
            foreach ($rows as $row) {
                $result[$row['project_id']] = (int) $row['row_count'];
            }
        }

        return $result;
    }

    /**
     * Touch batch of projects identified by an array od ids.
     *
     * @param array $ids Project ids
     */
    public static function batchTouch(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        /** @var User $user */
        $user = AngieApplication::authentication()->getAuthenticatedUser();

        if ($user) {
            DB::execute(
                'UPDATE projects SET updated_on = ?, updated_by_id = ?, updated_by_name = ?, updated_by_email = ? WHERE id IN (?)',
                DateTimeValue::now(),
                $user->getId(),
                $user->getDisplayName(true),
                $user->getEmail(),
                $ids,
            );
        }
    }

    public static function findNextProjectNumber(): int
    {
        $value = (int) DB::executeFirstCell('SELECT MAX(`project_number`) FROM `projects`');

        do {
            $value++;
        } while (self::projectWithNumberExists($value));

        return $value;
    }

    private static function projectWithNumberExists(int $project_number): bool
    {
        return (bool) DB::executeFirstCell(
            'SELECT COUNT(`id`) AS "row_count" FROM `projects` WHERE `project_number` = ?',
            $project_number,
        );
    }

    public static function getUniqueProjectHash(): string
    {
        do {
            $hash = make_string(10, 'abcdefghijklmnopqrstuvwxyz1234567890');
        } while (DB::executeFirstCell('SELECT COUNT(id) FROM projects WHERE project_hash = ?', $hash));

        return $hash;
    }

    /**
     * Revoke user from all projects where it is a member.
     */
    public static function revokeMember(User $user, User $by)
    {
        if (!$user->canChangeRole($by)) {
            throw new InsufficientPermissionsError();
        }

        /** @var Project[] $projects */
        $projects = self::findBySQL(
            'SELECT p.* FROM projects AS p LEFT JOIN project_users AS u ON p.id = u.project_id WHERE u.user_id = ?',
            $user->getId(),
        );

        if ($projects) {
            foreach ($projects as $project) {
                $project->removeMembers([$user], ['by' => $by]);
            }
        }
    }

    public static function getBudgetingData(): array
    {
        $result = DB::execute('
            SELECT p.id, p.name, p.company_id, c.name AS companyName, p.budget_type, p.budget, p.currency_id, cur.code AS currencyCode
            FROM projects AS p
            LEFT JOIN companies AS c ON p.company_id = c.id
            LEFT JOIN currencies AS cur ON p.currency_id = cur.id
            WHERE p.is_trashed = 0 AND p.is_tracking_enabled = 1 AND p.is_sample = 0 AND (p.completed_on IS NULL OR p.completed_on BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND NOW())');

        if ($result) {
            return $result->toArray();
        }

        return [];
    }

    public static function getProjectIdsByTaskIds(array $task_ids = []): array
    {
        if ($task_ids) {
            return array_unique(DB::executeFirstColumn('SELECT project_id FROM tasks WHERE id IN(?)', $task_ids));
        }

        return [];
    }

    private static function prepareSortByArchivedProjectsCollection(ModelCollection $collection): void
    {
        $ids = DB::executeFirstColumn(
            'SELECT t.id, SUM(t.size) AS size,t.completed_on FROM (SELECT p.id, a.size, p.completed_on
            FROM projects AS p
            LEFT JOIN attachments AS a ON a.project_id = p.id AND a.type IN (?)
            WHERE p.is_trashed = 0 AND p.completed_on IS NOT NULL
            UNION ALL
            SELECT p.id, f.size, p.completed_on
            FROM projects AS p
            LEFT JOIN files AS f ON f.project_id = p.id AND f.type IN (?)
            WHERE p.is_trashed = 0 AND p.completed_on IS NOT NULL
            ) AS t
            GROUP BY t.id, t.completed_on
            ORDER BY size DESC, completed_on DESC',
            [LocalAttachment::class, WarehouseAttachment::class],
            [LocalFile::class, WarehouseFile::class],
        );

        $collection->setOrderBy(DB::prepare('FIELD(projects.id, ?)', $ids));
    }
}
