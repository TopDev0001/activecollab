<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Job types manager class.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
class JobTypes extends BaseJobTypes
{
    /**
     * @var array
     */
    private static $id_name_map = false;
    private static $full_id_name_map = false;

    /**
     * Return new collection.
     *
     * Possibilities:
     *
     * - all
     * - all_for_logged_user
     * - all_for_1 (where 1 is user ID)
     * - active
     * - archived
     *
     * @param  User|null                 $user
     * @return ModelCollection
     * @throws InvalidParamError
     * @throws ImpossibleCollectionError
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if (str_starts_with($collection_name, 'all_for')) {
            if ($collection_name === 'all_for_logged_user') {
                $job_types_for = $user;
            } else {
                $bits = explode('_', $collection_name);

                $job_types_for_id = (int) array_pop($bits);
                $job_types_for = $job_types_for_id ? DataObjectPool::get('User', $job_types_for_id) : null;
            }

            if ($job_types_for instanceof User) {
                if (!$job_types_for->isPowerUser() && ConfigOptions::hasValueFor('job_type_id', $job_types_for)) {
                    $collection->setConditions('id = ?', ConfigOptions::getValueFor('job_type_id', $job_types_for));
                }
            } else {
                throw new ImpossibleCollectionError('User not found');
            }
        } elseif ($collection_name === self::ACTIVE) {
            $collection->setConditions('is_archived = ?', false);
        } elseif ($collection_name === self::ARCHIVED) {
            $collection->setConditions('is_archived = ?', true);
        } elseif ($collection_name !== DataManager::ALL) {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    /**
     * Return array of hourly rates for given project.
     *
     * @param  Company|Project      $context
     * @return array
     * @throws InvalidInstanceError
     */
    public static function getIdRateMapFor($context)
    {
        if ($context instanceof Company || $context instanceof Project) {
            return AngieApplication::cache()->getByObject($context, ['hourly_rates'], function () use ($context) {
                $result = [];

                if ($rows = DB::execute('SELECT id, default_hourly_rate FROM job_types ORDER BY name')) {
                    foreach ($rows as $row) {
                        $result[$row['id']] = (float) $row['default_hourly_rate'];
                    }

                    // Company overrides
                    if ($rows = DB::execute('SELECT job_type_id, hourly_rate FROM custom_hourly_rates WHERE parent_type = "Company" AND parent_id = ?', ($context instanceof Project ? $context->getCompanyId() : $context->getId()))) {
                        foreach ($rows as $row) {
                            $result[$row['job_type_id']] = (float) $row['hourly_rate'];
                        }
                    }

                    // Project overrides, if needed
                    if ($context instanceof Project) {
                        if ($rows = DB::execute('SELECT job_type_id, hourly_rate FROM custom_hourly_rates WHERE parent_type = "Project" AND parent_id = ?', $context->getId())) {
                            foreach ($rows as $row) {
                                $result[$row['job_type_id']] = (float) $row['hourly_rate'];
                            }
                        }
                    }
                }

                return $result;
            });
        }

        throw new InvalidInstanceError('context', $context, ['Project', 'Company']);
    }

    /**
     * Returns true if job type is in use within the project.
     *
     * @param  int  $job_type_id
     * @return bool
     */
    public static function getInUseByProject(Project $project, $job_type_id)
    {
        $estimate_parent_ids = DB::executeFirstColumn('SELECT parent_id FROM estimates WHERE job_type_id = ?', $job_type_id);
        $used_in_estimates = (bool) DB::executeFirstCell('SELECT COUNT(*) FROM project_objects WHERE id IN (?) AND project_id = ?', $estimate_parent_ids, $project->getId());

        if (!$used_in_estimates) {
            $time_record_parent_ids = DB::execute('SELECT parent_type, parent_id FROM time_records WHERE job_type_id = ?', $job_type_id);
            if (!($time_record_parent_ids instanceof DBResult)) {
                return false;
            }

            foreach ($time_record_parent_ids->toArray() as $parent) {
                if ($parent['parent_type'] == 'Project' && $parent['parent_id'] == $project->getId()) {
                    return true;
                }

                $time_record_project_object_ids[] = $parent['parent_id'];
            }

            return (bool) DB::executeFirstCell('SELECT COUNT(*) FROM project_objects WHERE id IN (?) AND project_id = ?', $time_record_project_object_ids, $project->getId());
        } else {
            return true;
        }
    }

    /**
     * Return job type name by job type ID.
     *
     * @param  int    $job_type_id
     * @return string
     */
    public static function getNameById($job_type_id)
    {
        $job_types_id_name_map = self::getIdNameMap();

        if (!empty($job_types_id_name_map)) {
            return array_var($job_types_id_name_map, $job_type_id);
        }

        return null;
    }

    /**
     * Return ID => name map.
     *
     * @param  bool  $include_archived
     * @param  bool  $use_cache
     * @return array
     */
    public static function getIdNameMap($include_archived = false, $use_cache = true)
    {
        if (!$use_cache || self::$id_name_map === false || self::$full_id_name_map === false) {
            self::$id_name_map = [];
            self::$full_id_name_map = [];

            if ($rows = DB::execute('SELECT id, name, is_archived FROM job_types ORDER BY name')) {
                foreach ($rows as $row) {
                    self::$full_id_name_map[$row['id']] = $row['name'];

                    if (!$row['is_archived']) {
                        self::$id_name_map[$row['id']] = $row['name'];
                    }
                }
            }
        }

        return $include_archived ? self::$full_id_name_map : self::$id_name_map;
    }

    /**
     * Return ID of the default job type.
     *
     * @return int
     */
    public static function getDefaultId()
    {
        return AngieApplication::cache()->get(['models', 'job_types', 'default_job_type_id'], function () {
            return DB::executeFirstCell('SELECT id FROM job_types ORDER BY is_default DESC LIMIT 0, 1');
        });
    }

    /**
     * Set default job type.
     *
     * @return JobType
     */
    public static function setDefault(JobType $job_type)
    {
        if ($job_type->getIsDefault()) {
            return $job_type; // Already default
        }

        if ($job_type->getIsArchived()) {
            throw new InvalidParamError('job_type', $job_type, 'Archived job types cannot be set as default');
        }

        DB::transact(function () use ($job_type) {
            DB::execute('UPDATE job_types SET is_default = ?', false);
            DB::execute('UPDATE job_types SET is_default = ? WHERE id = ?', true, $job_type->getId());

            AngieApplication::invalidateInitialSettingsCache();
        }, 'Make job type default');

        self::clearCache();

        return DataObjectPool::reload(JobType::class, $job_type->getId());
    }

    public static function canAdd(User $user): bool
    {
        return $user->isOwner();
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): JobType
    {
        if ($archived_job_type = self::getExistingJobTypeByAttributes($attributes, true)) {
            return parent::update(
                $archived_job_type,
                array_merge(
                    $attributes,
                    [
                        'is_archived' => false,
                    ]
                )
            );
        }

        return parent::create($attributes, $save, $announce);
    }

    /**
     * Return existing job type by attributes.
     *
     * @param  bool            $is_archived
     * @return DataObject|null
     */
    private static function getExistingJobTypeByAttributes(array $attributes, $is_archived = false)
    {
        if ($job_type_id = DB::executeFirstCell('SELECT id FROM job_types WHERE name = ? AND is_archived = ?', array_var($attributes, 'name'), $is_archived)) {
            return self::findById($job_type_id);
        }

        return null;
    }

    /**
     * @return DataObject|JobType|bool
     */
    public static function scrap(
        DataObject &$instance,
        bool $force_delete = false
    )
    {
        if ($instance->inUse()) {
            self::updateDefaultJobTypeForUsers($instance->getId());

            return parent::update($instance, ['is_archived' => true]);
        }

        return parent::scrap($instance, $force_delete);
    }

    /**
     * Update users default job type to global default job type if they have archived job type set.
     *
     * @throws Exception
     */
    private static function updateDefaultJobTypeForUsers(int $archived_job_type_id)
    {
        try {
            DB::execute('
            UPDATE config_option_values 
            SET value = ? 
            WHERE name = ?
            AND parent_type = ? 
            AND value = ?',
                serialize(self::getDefaultId()),
                'default_job_type_id',
                User::class,
                serialize($archived_job_type_id)
            );
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Edit an array of job types.
     *
     * sample call of this method:
     *
     * $results = JobTypes::batchEdit([
     *     ['id' => 1, 'name' => 'foo', 'default_hourly_rate' => 27],
     *     ['id' => 2, 'name' => 'bar'],
     *     ['id' => 36, 'default_hourly_rate' => 45],
     * ]);
     *
     * @return array|JobType[]
     */
    public static function batchEdit(array $job_types)
    {
        $instances = [];
        foreach ($job_types as $job_type_attributes) {
            /** @var JobType $instance */
            if (($instance = DataObjectPool::get(JobType::class, $job_type_attributes['id']))) {
                $instance->setAttributes($job_type_attributes);
                $instance->save();
                $instances[] = $instance;
            }
        }

        self::batchTouchParents($instances);

        return $instances;
    }

    /**
     * Touches companies and projects which doesn't have custom hourly rate for passed array of job types
     * in one SQL query and clears their caches.
     *
     * @param JobType[] $instances
     */
    public static function batchTouchParents(array $instances)
    {
        $company_ids = [];
        $project_ids = [];
        $companies = Companies::find();
        foreach ($instances as $job_type) {
            //touch companies and projects which doesn't have custom hourly rates for the given job type
            foreach ($companies as $company) {
                /** @var Company $company */
                if ($company instanceof Company) {
                    if (!$job_type->hasCustomHourlyRateFor($company)) {
                        $company_ids[] = $company->getId();
                    }

                    /** @var Project[] $projects */
                    if ($projects = $company->getActiveProjects()) {
                        foreach ($projects as $project) {
                            if (!$job_type->hasCustomHourlyRateFor($project)) {
                                $project_ids[] = $project->getId();
                            }
                        }
                    }
                }
            }
        }

        // batch touch companies
        Companies::batchTouch(array_unique($company_ids));
        Companies::clearCacheFor(array_unique($company_ids));

        // batch touch projects
        Projects::batchTouch(array_unique($project_ids));
        Projects::clearCacheFor(array_unique($project_ids));
    }
}
