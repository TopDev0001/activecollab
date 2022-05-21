<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\ExecuteActiveCollabCliCommand;
use ActiveCollab\Module\System\Utils\DateValidationResolver\TaskDateValidationResolver;

abstract class AbstractImporterIntegration extends Integration
{
    const API_CONTACT = 'https://www.activecollab.com/about-contact.html';

    const DOWNLOAD_FILE_CHANNEL = 'download';
    const MIGRATION_CHANNEL = 'migration';

    // status constants
    const STATUS_NORMAL = 'normal';
    const STATUS_PENDING = 'pending';
    const STATUS_IMPORTING = 'importing';
    const STATUS_INVITING = 'inviting';
    const STATUS_FINISHED = 'finished';
    const STATUS_FAILED = 'failed';

    /**
     * Mapping table.
     *
     * @var DBTable
     */
    protected $mapping_table;

    /**
     * Should we send emails.
     *
     * @var bool
     */
    protected $send_emails = false;

    /**
     * Timestamp.
     *
     * @var int
     */
    private $start_request_batch;

    public function isInUse(User $user = null): bool
    {
        return !empty($this->getAdditionalProperty('status'));
    }

    public function isSingleton(): bool
    {
        return true;
    }

    /**
     * Return short integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang('Copy your data to ActiveCollab');
    }

    /**
     * Get group of this integration.
     *
     * @return string
     */
    public function getGroup()
    {
        return 'migration_tools';
    }

    /**
     * Get open action name.
     *
     * @return string
     */
    public function getOpenActionName()
    {
        return lang('Import');
    }

    /**
     * Set import total.
     *
     * @param int $total
     *
     * @return int
     */
    protected function setImportTotal($total = 0)
    {
        return $this->setAdditionalProperty('import_total', $total);
    }

    /**
     * Set import progress.
     *
     * @param int $progress
     *
     * @return int
     */
    protected function setImportProgress($progress = 0)
    {
        return $this->setAdditionalProperty('import_progress', $progress);
    }

    /**
     * Set import label.
     *
     * @param string $label
     *
     * @return int
     */
    public function setImportLabel($label)
    {
        return $this->setAdditionalProperty('import_label', $label);
    }

    /**
     * Get import label.
     *
     * @return string
     */
    public function getImportLabel()
    {
        if ($this->getStatus() == self::STATUS_IMPORTING) {
            return $this->getAdditionalProperty('import_label', '');
        } else {
            return '';
        }
    }

    /**
     * Set importer status.
     *
     * @param string $status
     *
     * @return bool
     */
    protected function setStatus($status)
    {
        return $this->setAdditionalProperty('status', $status);
    }

    /**
     * Get the importer status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getAdditionalProperty('status') ? $this->getAdditionalProperty('status') : self::STATUS_NORMAL;
    }

    /**
     * Get import progress.
     *
     * @return int
     */
    public function getImportProgress()
    {
        if ($this->getStatus() == self::STATUS_IMPORTING) {
            return $this->getAdditionalProperty('import_progress', 0);
        } else {
            return 0;
        }
    }

    /**
     * Get import total.
     *
     * @return int
     */
    public function getImportTotal()
    {
        if ($this->getStatus() == self::STATUS_IMPORTING) {
            return $this->getAdditionalProperty('import_total', 0);
        } else {
            return 0;
        }
    }

    /**
     * Write in output buffer.
     *
     * @param string|array $messages
     */
    protected function outputBuffer(callable $output, $messages)
    {
        if ($output) {
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    $output($message);
                }
            } else {
                $output($messages);
            }
        }
    }

    /**
     * Return mapping table name.
     *
     * @param  string $table_name
     * @return string
     */
    protected function getMappingTableName($table_name)
    {
        if (empty($this->mapping_table)) {
            if (DB::tableExists($table_name)) {
                $this->mapping_table = DB::loadTable($table_name);
            } else {
                $this->mapping_table = DB::createTable($table_name)->addColumns([
                    DBStringColumn::create('object_type', 50, ''),
                    DBStringColumn::create('tool_object_id', 50, ''), // some tools (Trello) has ID as "4ee7df1be582acdec80000ae"
                    DBIntegerColumn::create('ac_object_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                ])->addIndices([
                    new DBIndexPrimary(['object_type', 'tool_object_id', 'ac_object_id']),
                    DBIndex::create('unique_index', DBIndex::UNIQUE, ['object_type', 'tool_object_id']),
                ]);
                $this->mapping_table->save();
            }
        }

        return $this->mapping_table->getName();
    }

    /**
     * Update mapping table.
     *
     * @param string $table_name
     */
    protected function migrateMappingTable($table_name)
    {
        if (DB::tableExists($table_name)) {
            /** @var DBTable $mapping_table */
            $mapping_table = DB::loadTable($table_name);

            // use new column name (used for basecamp)
            if ($table_name === 'basecamp_migration_mappings' &&
                in_array('bc_object_id', DB::listTableFields($table_name))) {
                $mapping_table->alterColumn('bc_object_id', DBStringColumn::create('tool_object_id', 50, ''));
            }

            // create unique index if not exists (used for both trello and basecamp)
            if (!$mapping_table->getIndex('unique_index')) {
                $mapping_table->addIndices([
                    DBIndex::create('unique_index', DBIndex::UNIQUE, ['object_type', 'tool_object_id']),
                ]);
            }
        }
    }

    /**
     * Return mapped object instance by tool ID.
     *
     * @param string $migration_mappings_table
     * @param string $tool_id
     * @param string $object_type
     *
     * @return object|null
     */
    protected function getMappedObjectType($migration_mappings_table, $tool_id, $object_type)
    {
        switch ($object_type) {
            case 'Workspace':
            case 'Board':
            case 'Project':
                $ac_object = 'Project';

                break;
            case 'User':
                $ac_object = 'User';

                break;
            case 'Label':
                $ac_object = 'Label';

                break;
            case 'Team':
                $ac_object = 'Team';

                break;
            case 'TaskList':
                $ac_object = 'TaskList';

                break;
            case 'Task':
                $ac_object = 'Task';
        }

        if (empty($ac_object)) {
            return null;
        }

        if ($id = DB::executeFirstCell('SELECT ac_object_id FROM '.$this->getMappingTableName($migration_mappings_table).' WHERE object_type = ? AND tool_object_id = ?', $object_type, $tool_id)) {
            return DataObjectPool::get($ac_object, $id);
        }

        return null;
    }

    /**
     * Map ActiveCollab object ID with Tool object.
     *
     * @param string $migration_mappings_table
     * @param string $tool_id
     * @param int    $ac_id
     * @param string $object_type
     */
    protected function mapObject($migration_mappings_table, $tool_id, $ac_id, $object_type)
    {
        DB::execute('REPLACE INTO '.$this->getMappingTableName($migration_mappings_table).' (object_type, tool_object_id, ac_object_id) VALUES (?, ?, ?)', $object_type, $tool_id, $ac_id);
    }

    /**
     * Truncate string to $length.
     *
     * @param string $string
     * @param int    $length
     *
     * @return string
     */
    protected function maxLength($string, $length = 150)
    {
        return !empty($string) ? mb_substr(trim($string), 0, $length) : 'Untitled Task';
    }

    /**
     * Complete object.
     *
     * @param  bool         $gag_modification_log
     * @throws \Angie\Error
     */
    protected function completeObject(ApplicationObject $object, User $by, DateTimeValue $on = null, $gag_modification_log = false)
    {
        if (!$on instanceof DateTimeValue) {
            $on = DateTimeValue::now();
        }

        switch (strtolower(get_class($object))) {
            case 'tasklist':
                $object_table_name = 'task_lists';

                break;
            case 'task':
                $object_table_name = 'tasks';

                break;
            case 'subtask':
                $object_table_name = 'subtasks';

                break;
            case 'project':
                $object_table_name = 'projects';

                break;
        }

        if (isset($object_table_name)) {
            DB::execute("UPDATE $object_table_name SET completed_on = ?, completed_by_id = ?, completed_by_name = ?, completed_by_email = ? WHERE id = ?", $on, $by->getId(), $by->getName(), $by->getEmail(), $object->getId());

            if ($object instanceof IHistory && !$gag_modification_log) {
                DB::execute('INSERT INTO modification_logs (parent_type, parent_id, created_on, created_by_id, created_by_name, created_by_email) VALUES (?, ?, ?, ?, ?, ?)', get_class($object), $object->getId(), $on->toMySQL(), $by->getId(), $by->getName(), $by->getEmail());
                DB::execute('INSERT INTO modification_log_values (modification_id, field, old_value, new_value) VALUES (?, ?, ?, ?)', DB::lastInsertId(), 'completed_on', null, $on);
            }
        } else {
            throw new Angie\Error('Cannot complete object. Invalid type');
        }
    }

    /**
     * Return process id.
     *
     * @return int
     */
    protected function getProcessId()
    {
        return $this->getAdditionalProperty('process_id');
    }

    /**
     * Remove process ID.
     */
    protected function resetProcesId()
    {
        return $this->setAdditionalProperty('process_id', null);
    }

    /**
     * Record process ID.
     */
    protected function recordProcessId()
    {
        if (DIRECTORY_SEPARATOR != '\\') {
            $this->setAdditionalProperty('process_id', getmypid());
        }
    }

    /**
     * Wizard is not supported.
     *
     * @return bool
     */
    protected function isWizardSupported()
    {
        return DIRECTORY_SEPARATOR != '\\';
    }

    /**
     * Check if process is active.
     *
     * @return bool
     */
    protected function isProcessActive()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            return true;
        }

        if (!$this->getProcessId()) {
            return false;
        }

        // get the list of currently active job processes
        $active_processes = AngieApplication::jobs()->getQueue()->getBackgroundProcesses();
        if (!count($active_processes)) {
            return false;
        }

        // try to find process in list of started processes
        foreach ($active_processes as $active_process) {
            if ($active_process['process_id'] == $this->getProcessId()) {
                return true;
            }
        }

        // if process is not found then we consider it failed
        return false;
    }

    /**
     * Check is email [at]example.com.
     *
     * @param  string $email
     * @return bool
     */
    protected function isEmailAtExample($email)
    {
        return strpos($email, '@example.com') !== false ? true : false;
    }

    /**
     * Start import process.
     *
     * @return bool
     * @throws Exception
     */
    public function startImport(callable $output = null)
    {
        if ($this->isProcessActive()) {
            throw new Exception('Import process is already in progress.');
        }

        $this->recordProcessId();
        $this->save();

        AngieApplication::log()->event($this->getLogEventPrefix() . '_import_started', 'Import started');

        return false; // Subclasses should change this
    }

    /**
     * Send users invite.
     *
     * @param  string $migration_mappings_table
     * @return $this
     */
    protected function inviteUsers($migration_mappings_table)
    {
        $send_by = Users::findFirstOwner();

        $mapped_users_ids = DB::execute('SELECT ac_object_id FROM ' . $this->getMappingTableName($migration_mappings_table) . ' WHERE object_type = ?', 'User');

        $mapped_users_ids = $mapped_users_ids ? $mapped_users_ids->toArray() : [];

        if (!empty($mapped_users_ids)) {
            $users = Users::findByIds($mapped_users_ids);

            if ($users) {
                foreach ($users as $user) {
                    if (!$this->isEmailAtExample($user->getEmail()) && $user->canBeInvited()) {
                        $user->invite($send_by, null, true);
                        ConfigOptions::setValueFor(['notifications_user_send_morning_paper' => true], $user); // enable morning mail to invited user
                    }
                }
            }
        }

        $this->setStatus(self::STATUS_FINISHED);
        $this->save();

        AngieApplication::log()->event($this->getLogEventPrefix() . '_import_finished', 'Import finished');

        return $this;
    }

    /**
     * Start import process over again.
     *
     * @return $this
     */
    public function &startOver()
    {
        $this->setStatus(self::STATUS_NORMAL);
        $this->setImportProgress(null);
        $this->setImportTotal(null);
        $this->resetProcesId();
        $this->save();

        AngieApplication::log()->event($this->getLogEventPrefix() . '_import_restarted', 'Import restarted');

        return $this;
    }

    /**
     * Check progress of the import.
     *
     * @return $this
     * @throws Exception
     */
    public function &checkStatus()
    {
        if ($this->getStatus() == self::STATUS_IMPORTING && !$this->isProcessActive()) {
            $this->setStatus(self::STATUS_FAILED);
            $this->resetProcesId();
            $this->save();

            AngieApplication::log()->error('Import failed because status is "{status}" and process does not exist', [
                'account_id' => AngieApplication::getAccountId(),
                'status' => self::STATUS_IMPORTING,
            ]);
        }

        return $this;
    }

    /**
     * Start request batch.
     *
     * @param int $timestamp
     */
    protected function startRequestBatch($timestamp) {
        $this->start_request_batch = $timestamp;
    }

    /**
     * Get request batch time.
     *
     * @param  int $time_limit
     * @return int
     */
    protected function getRequestBatchTime($time_limit) {
        return $time_limit - ((DateTimeValue::now()->getTimestamp() - $this->start_request_batch) % $time_limit);
    }

    /**
     * Dispatch job.
     *
     * @throws Exception
     */
    protected function dispatchJob(array $job_data)
    {
        if ($this->isProcessActive()) {
            throw new Exception('Import process is already in progress.');
        }

        // create job
        $job = new ExecuteActiveCollabCliCommand(
            array_merge(
                [
                    'instance_id' => AngieApplication::getAccountId(),
                    'instance_type' => 'feather',
                    'tasks_path' => ENVIRONMENT_PATH . '/tasks',
                    'in_background' => true,
                ],
                $job_data
            )
        );

        // dispatch job
        AngieApplication::jobs()->dispatch($job, self::MIGRATION_CHANNEL);
    }

    public function saveTask(Task &$task)
    {
        $task_date_validation_resolver = AngieApplication::getContainer()->get(TaskDateValidationResolver::class);

        if (
            $task->getDueOn() instanceof DateValue &&
            !$task_date_validation_resolver->isValid($task->getDueOn())
        ) {
            $task->setDueOn(null);
        }

        if (
            $task->getStartOn() instanceof DateValue &&
            !$task_date_validation_resolver->isValid($task->getStartOn())
        ) {
            $task->setStartOn(null);
        }

        $task->save();
    }

    abstract protected function getLogEventPrefix(): string;
}
