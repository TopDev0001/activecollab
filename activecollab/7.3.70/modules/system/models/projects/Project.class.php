<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents\ProjectMembershipGrantedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents\ProjectMembershipRevokedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents\ProjectMoveToTrashEvent;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use ActiveCollab\Module\System\Utils\TemplateApplicator\Result\TemplateApplicationResultInterface;
use Angie\Inflector;
use Angie\Mailer;
use Angie\Search\SearchDocument\SearchDocumentInterface;

class Project extends BaseProject implements RoutingContextInterface, IConfigContext, IIncomingMail
{
    use ICalendarFeedImplementation;
    use RoutingContextImplementation;

    public const STATUS_ANY = 'any';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    public const BUDGET_TYPE_FIXED = 'fixed';
    public const BUDGET_TYPE_PAY_AS_YOU_GO = 'pay_as_you_go';
    public const BUDGET_NOT_BILLABLE = 'not_billable';

    public const BUDGET_TYPES = [
        self::BUDGET_TYPE_FIXED,
        self::BUDGET_TYPE_PAY_AS_YOU_GO,
        self::BUDGET_NOT_BILLABLE,
    ];

    protected ?array $protect = [
        'id',
        'completed_on',
        'completed_by_id',
        'completed_by_name',
        'completed_by_email',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
    ];

    /**
     * Should touch action update last_activity_on field.
     *
     * @var bool
     */
    protected $touch_updates_activity = true;

    /**
     * Cached based on instance.
     *
     * @var ApplicationObject
     */
    protected $based_on = false;

    /**
     * Cached cost so far value.
     *
     * @var float
     */
    private $cost_so_far = false;

    /**
     * Cached parent filter value.
     *
     * @var string
     */
    private $project_elements_parent_filter = false;
    private bool $is_untouchable = false;

    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'company_id',
                'currency_id',
                'budget',
                'budget_type',
                'leader_id',
                'body',
                'is_billable',
                'members_can_change_billable',
                'is_tracking_enabled',
            ],
        );
    }

    public function getSearchFields(): array
    {
        return array_merge(
            parent::getSearchFields(),
            [
                'body',
                'leader_id',
                'label_id',
            ],
        );
    }

    public function getVerboseType(bool $lowercase = false, Language $language = null): string
    {
        return $lowercase
            ? lang('project', null, true, $language)
            : lang('Project', null, true, $language);
    }

    public function applyTemplate(
        ProjectTemplate $template,
        User $by,
        bool $update_first_task_list = true,
        DateValue $template_date_reference = null
    ): TemplateApplicationResultInterface
    {
        try {
            DB::beginWork('Apply a template @ ' . __CLASS__);

            $template_application_result = $template->copyItems(
                $this,
                $by,
                $update_first_task_list,
                $template_date_reference,
            );

            AppliedProjectTemplates::create(
                [
                    'project_id' => $this->getId(),
                    'template_id' => $template->getId(),
                ],
            );

            DB::commit('Template applied @ ' . __CLASS__);

            return $template_application_result;
        } catch (Exception $e) {
            DB::rollback('Failed to apply a template @ ' . __CLASS__);

            throw $e;
        }
    }

    public function getAppliedTemplateIds(): array
    {
        $applied_template_ids = DB::executeFirstColumn(
            'SELECT `id` FROM `applied_project_templates` WHERE `project_id` = ?',
            $this->getId(),
        );

        if (empty($applied_template_ids)) {
            $applied_template_ids = [];
        }

        return $applied_template_ids;
    }

    public function getLatestUsedTemplateId(): int
    {
        return (int) DB::executeFirstCell(
            'SELECT `template_id` FROM `applied_project_templates` WHERE `project_id` = ? ORDER BY `created_on` DESC',
            $this->getId(),
        );
    }

    /**
     * Save project.
     */
    public function save()
    {
        try {
            DB::beginWork('Saving project @ ' . __CLASS__);

            if (!$this->getCompanyId()) {
                $this->setCompanyId(
                    AngieApplication::getContainer()
                        ->get(OwnerCompanyResolverInterface::class)
                            ->getId(),
                );
            }

            if (!$this->getProjectNumber()) {
                $this->setProjectNumber(Projects::findNextProjectNumber());
            }

            if (!$this->getProjectHash()) {
                $this->setProjectHash(Projects::getUniqueProjectHash());
            }

            if (!$this->getCurrencyId()) {
                if ($company = $this->getCompany()) {
                    $this->setCurrencyId($company->getCurrencyId());
                }

                if (!$this->getCurrencyId()) {
                    $this->setCurrencyId(Currencies::getDefaultId());
                }
            }

            $client_reporting_changed = $this->isModifiedField('is_client_reporting_enabled');

            parent::save();

            if ($client_reporting_changed) {
                $this->rebuildTrackingUpdates();
            }

            AngieApplication::cache()->clearModelCache();

            DB::commit('Project saved @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to save project @ ' . __CLASS__);

            throw $e;
        }
    }

    public function getCompany(): Company
    {
        $project_company = DataObjectPool::get(Company::class, $this->getCompanyId());

        if ($project_company instanceof Company) {
            return $project_company;
        }

        return AngieApplication::getContainer()
            ->get(OwnerCompanyResolverInterface::class)
                ->getCompany();
    }

    /**
     * Return parent project that this object is based on.
     *
     * @return IProjectBasedOn
     */
    public function getBasedOn()
    {
        if ($this->based_on === false) {
            $based_on_class = $this->getBasedOnType();
            $based_on_id = $this->getBasedOnId();

            if ($based_on_class && $based_on_id) {
                $this->based_on = new $based_on_class($based_on_id);

                if (!($this->based_on instanceof IProjectBasedOn)) {
                    $this->based_on = null;
                }
            } else {
                $this->based_on = null;
            }
        }

        return $this->based_on;
    }

    /**
     * Set project based on value.
     *
     * @param  ApplicationObject|IProjectBasedOn $value
     * @param  bool                              $save
     * @return IProjectBasedOn|null
     */
    public function setBasedOn($value, $save = false)
    {
        if ($value instanceof IProjectBasedOn) {
            $this->setBasedOnType(get_class($value));
            $this->setBasedOnId($value->getId());
        } elseif ($value === null) {
            $this->setBasedOnType(null);
            $this->setBasedOnId(null);
        } else {
            throw new InvalidInstanceError('value', $value, 'ApplicationObject');
        }

        $this->based_on = $value;

        if ($save) {
            $this->save();
        }

        return $this->based_on;
    }

    /**
     * Return project leader.
     *
     * @return User|DataObject|null
     */
    public function getLeader()
    {
        return DataObjectPool::get(User::class, $this->getLeaderId());
    }

    /**
     * Return project currency.
     *
     * @return Currency|DataObject
     */
    public function getCurrency()
    {
        return DataObjectPool::get(Currency::class, $this->getCurrencyId(), function () {
            return Currencies::getDefault();
        });
    }

    /**
     * Set currency value.
     *
     * $currency can be Currency instance, or NULL. In case of NULL, this
     * project will use default currency
     *
     * @param  Currency $currency
     * @param  bool     $save
     * @return int
     */
    public function setCurrency($currency, $save = false)
    {
        if ($currency instanceof Currency) {
            $this->setCurrencyId($currency->getId());
        } elseif ($currency === null) {
            $this->setCurrencyId(0);
        } else {
            throw new InvalidInstanceError('currency', $currency, Currency::class);
        }

        if ($save) {
            $this->save();
        }

        return $this->getCurrencyId();
    }

    /**
     * Return cost so far in percent.
     *
     * @return float
     */
    public function getCostSoFarInPercent(IUser $user)
    {
        if ($this->getBudget() > 0) {
            $cost_so_far = $this->getCostSoFar($user);

            if ($cost_so_far > 0) {
                return ceil(($cost_so_far * 100) / $this->getBudget());
            } else {
                return 0;
            }
        } else {
            return null;
        }
    }

    /**
     * Return cost so far.
     *
     * @param  User|IUser $user
     * @return float
     */
    public function getCostSoFar(IUser $user)
    {
        if ($this->cost_so_far === false) {
            $this->cost_so_far = TrackingObjects::sumCostByProject($this, $user);
        }

        return $this->cost_so_far;
    }

    /**
     * Return verbose status.
     *
     * @return string
     */
    public function getVerboseStatus()
    {
        return $this->getCompletedOn() instanceof DateValue ? lang('Completed') : lang('Active');
    }

    /**
     * Set attributes.
     *
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        if (isset($attributes['budget'])) {
            $attributes['budget'] = moneyval($attributes['budget']);
        }

        parent::setAttributes($attributes);
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['project_number'] = $this->getProjectNumber();
        $result['body'] = $this->getBody();
        $result['body_formatted'] = $result['body'];

        if (is_string($result['body'])) {
            $result['body_formatted'] = nl2br($result['body']);
        }

        $result['company_id'] = $this->getCompanyId();
        $result['leader_id'] = $this->getLeaderId();
        $result['currency_id'] = $this->getCurrencyId();

        $result['based_on_type'] = $this->getBasedOnType();
        $result['based_on_id'] = $this->getBasedOnId();

        $result['email'] = $this->getMailToProjectEmail();

        $result['is_tracking_enabled'] = $this->getIsTrackingEnabled();
        $result['is_billable'] = $this->getIsBillable();
        $result['members_can_change_billable'] = $this->getMembersCanChangeBillable();
        $result['is_client_reporting_enabled'] = $this->getIsClientReportingEnabled();
        $result['is_sample'] = $this->getIsSample();

        $result['budget_type'] = $this->getBudgetType();
        $result['budget'] = $this->getBudget();

        $result['count_tasks'] = Tasks::countOpenByProject($this);
        $result['count_discussions'] = Discussions::countByProject($this);
        $result['count_files'] = Files::countByProject($this);
        $result['count_notes'] = Notes::countByProject($this);
        $result['last_activity_on'] = $this->getLastActivityOn();
        $result['file_size'] = Files::fileSizeSumByProject($this);

        return $result;
    }

    /**
     * Returns mail to project address.
     */
    public function getMailToProjectEmail(): string
    {
        if (AngieApplication::isOnDemand()) {
            return sprintf(
                'notifications-%s+%s@activecollab.email',
                AngieApplication::getAccountId(),
                $this->getProjectHash(),
            );
        }

        $mail_pieces = explode('@', Mailer::getDefaultSender()->getEmail());

        return $mail_pieces[0] . '+' . $this->getProjectHash() . '@' . $mail_pieces[1];
    }

    /**
     * Describe single.
     */
    public function describeSingleForFeather(array &$result)
    {
        parent::describeSingleForFeather($result);

        $result['hourly_rates'] = $this->getHourlyRates();
        $result['label_ids'] = Labels::getLabelIdsByProject($this);
        $result['task_lists'] = TaskLists::find(
            [
                'conditions' => [
                    'project_id = ? AND completed_on IS NULL AND is_trashed = ?',
                    $this->getId(),
                    false,
                ],
            ],
        );
        $result['count_open_tasks'] = Tasks::countOpenTaskByProjectAndByRole($this);
        $result['count_completed_tasks'] = Tasks::countCompletedTaskByProjectAndByUserRole($this);
        $result['applied_template_ids'] = $this->getAppliedTemplateIds();
    }

    public function complete(User $by, bool $bulk = false)
    {
        try {
            DB::beginWork('Begin: mark project as complete @ ' . __CLASS__);

            parent::complete($by, $bulk);

            /** @var TaskList[] $task_lists */
            $task_lists = TaskLists::find(
                [
                    'conditions' => [
                        'project_id = ? AND completed_on IS NULL',
                        $this->getId(),
                    ],
                ],
            );

            if ($task_lists) {
                foreach ($task_lists as $task_list) {
                    $task_list->complete($by, true);
                }
            }

            $tasks = Tasks::find(
                [
                    'conditions' => [
                        'project_id = ? AND completed_on IS NULL',
                        $this->getId(),
                    ],
                    'order' => 'position',
                ],
            );

            if ($tasks) {
                foreach ($tasks as $task) {
                    $task->complete($by, true);
                }
            }

            $project_id = DB::escape($this->getId());
            $project_completed_on = DB::escape($this->getCompletedOn());

            DB::execute("UPDATE task_lists SET completed_on = $project_completed_on WHERE project_id = $project_id AND completed_on > $project_completed_on");
            DB::execute("UPDATE tasks SET completed_on = $project_completed_on WHERE project_id = $project_id AND completed_on > $project_completed_on");
            DB::execute("UPDATE subtasks SET completed_on = $project_completed_on WHERE task_id IN (SELECT id FROM tasks WHERE project_id = $project_id) AND completed_on > $project_completed_on");

            DB::commit('Done: mark project as complete @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: mark project as complete @ ' . __CLASS__);

            throw $e;
        }
    }

    public function open(User $by, bool $bulk = false)
    {
        if ($this->isCompleted()) {
            try {
                DB::beginWork('Begin: reopen project @ ' . __CLASS__);

                if (!$bulk) {
                    /** @var TaskList[] $task_lists */
                    if ($task_lists = TaskLists::find(['conditions' => ['project_id = ? AND completed_on = ?', $this->getId(), $this->getCompletedOn()]])) {
                        foreach ($task_lists as $task_list) {
                            $task_list->open($by, true);
                        }
                    }

                    $tasks = Tasks::find(
                        [
                            'conditions' => [
                                'project_id = ? AND completed_on = ?',
                                $this->getId(),
                                $this->getCompletedOn(),
                            ],
                            'order' => 'position',
                        ],
                    );

                    if ($tasks) {
                        foreach ($tasks as $task) {
                            $task->open($by, true);
                        }
                    }

                    $subtasks = Subtasks::findBySQL(
                        'SELECT * FROM subtasks WHERE task_id IN (SELECT id FROM tasks WHERE project_id = ?) AND completed_on = ? ORDER BY position',
                        $this->getId(),
                        $this->getCompletedOn(),
                    );

                    if ($subtasks) {
                        foreach ($subtasks as $subtask) {
                            $subtask->open($by, true);
                        }
                    }
                }

                parent::open($by, $bulk);

                DB::commit('Done: reopen project @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Rollback: reopen project @ ' . __CLASS__);

                throw $e;
            }
        }
    }

    public function canView(User $user): bool
    {
        return $user->isOwner() || $this->isMember($user);
    }

    public function canDelete(User $user): bool
    {
        return $this->canEdit($user);
    }

    public function canEdit(User $user): bool
    {
        return $user->isOwner() || $this->isLeader($user) || ($user->isPowerUser() && $this->isCreatedBy($user));
    }

    public function isLeader(User $user): bool
    {
        return $user->isLoaded() && $this->getLeaderId() == $user->getId();
    }

    public function canSeeBudget(User $user): bool
    {
        if (!$this->getIsTrackingEnabled()) {
            return false;
        }

        if ($user instanceof Owner) {
            return true; // Owner always have all permissions
        }

        if (!$this->isMember($user)) {
            return false;
        }

        if ($user instanceof Client) {
            return $this->getIsClientReportingEnabled(); // Clients can see budget if reporting is enabled
        }

        return $this->isLeader($user)
            || $user->isPowerUser()
            || $user->isFinancialManager(); // Project leader, or members who can create new projects or manage finances can see project budget
    }

    /**
     * Return true if $user can view access logs.
     *
     * @return bool
     */
    public function canViewAccessLogs(User $user)
    {
        return $user->isPowerUser() || $this->isLeader($user);
    }

    /**
     * Check if user can invite people on given project.
     *
     * @return bool
     */
    public function canManagePeople(User $user)
    {
        return $user->isOwner() || $this->isLeader($user) || ($user->isPowerUser() && $this->isCreatedBy($user));
    }

    public function getRoutingContext(): string
    {
        return 'project';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'project_id' => $this->getId(),
        ];
    }

    /**
     * Return URL path.
     */
    public function getUrlPath(): string
    {
        return '/projects/' . $this->getId();
    }

    public function getSearchDocument(): SearchDocumentInterface
    {
        return new ProjectSearchDocument($this);
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Process incoming mail and return resulting object (or null if message can't be handled).
     *
     * @param  IUser[]         $to
     * @param  string          $subject
     * @param  string          $text
     * @return DataObject|null
     */
    public function processIncomingMail(IUser $from, array $to, $subject, $text, array $attachments = null)
    {
        $create_a_task = false;

        if (str_starts_with($subject, '# ')) {
            $create_a_task = true;
            $subject = trim(substr_utf($subject, 1));
        }

        $task_list = TaskLists::getFirstTaskList($this);

        $properties = [
            'name' => $subject,
            'body' => $text,
            'project_id' => $this->getId(),
            'task_list_id' => $task_list->getId(),
            'subscribers' => [($from instanceof User ? $from->getId() : [$from->getName(), $from->getEmail()])],
            'attach_uploaded_files' => $attachments,
        ];

        if ($from instanceof User) {
            $properties['created_by_id'] = $from->getId();
        } else {
            $properties['created_by_name'] = $from->getName();
            $properties['created_by_email'] = $from->getEmail();
        }

        if ($this->getLeaderId()) {
            $properties['subscribers'][] = $this->getLeaderId();
        }

        foreach ($to as $user_to_subscribe) {
            if ($user_to_subscribe instanceof User && $this->isMember($user_to_subscribe)) {
                $properties['subscribers'][] = $user_to_subscribe->getId();
            } else {
                if ($user_to_subscribe instanceof AnonymousUser) {
                    $properties['subscribers'][] = [$user_to_subscribe->getName(), $user_to_subscribe->getEmail()];
                }
            }
        }

        $result = $create_a_task ? Tasks::create($properties) : Discussions::create($properties);

        if (ConfigOptions::getValue('notifications_notify_email_sender')) {
            /** @var NotifyEmailSenderNotification $notification */
            $notification = AngieApplication::notifications()->notifyAbout('system/notify_email_sender');
            $notification
                ->setEmailAddress($this->getMailToProjectEmail())
                ->sendToUsers($from);
        }

        return $result;
    }

    // ---------------------------------------------------
    //  Files Context
    // ---------------------------------------------------

    /**
     * Validate model object before save.
     */
    public function validate(ValidationErrors &$errors)
    {
        $this->validatePresenceOf('company_id') or $errors->fieldValueIsRequired('company_id');
        $this->validatePresenceOf('name') or $errors->fieldValueIsRequired('name');

        if ($this->validatePresenceOf('mail_to_project_email')) {
            if (is_valid_email($this->getMailToProjectEmail())) {
                $this->validateUniquenessOf('mail_to_project_email') or $errors->fieldValueNeedsToBeUnique('mail_to_project_emailx');
            } else {
                $errors->addError('Invalid email address', 'mail_to_project_email');
            }
        }
    }

    /**
     * Return notification subject prefix.
     *
     * @return string
     */
    public function getNotificationSubjectPrefix()
    {
        return '[' . $this->getName() . '] ';
    }

    // ---------------------------------------------------
    //  Project members
    // ---------------------------------------------------

    /**
     * Return type - IDs map of potential attachment parents in this context.
     *
     * @return array|null
     */
    public function getTypeIdsMapOfPotentialAttachmentParents()
    {
        $result = [];

        $table_and_types = [
            'tasks' => 'Task',
            'discussions' => 'Discussion',
            'files' => null,
        ];

        $project_id = DB::escape($this->getId());
        $not_trashed = DB::escape(false);

        foreach ($table_and_types as $table => $type) {
            if ($type) {
                $ids = DB::executeFirstColumn("SELECT id FROM $table WHERE project_id = $project_id AND is_trashed = $not_trashed");

                if ($ids) {
                    $result[$type] = $ids;
                }
            } else {
                if ($rows = DB::execute("SELECT id, type FROM $table WHERE project_id = $project_id AND is_trashed = $not_trashed")) {
                    foreach ($rows as $row) {
                        if (empty($result[$row['type']])) {
                            $result[$row['type']] = [];
                        }

                        $result[$row['type']][] = $row['id'];
                    }
                }
            }
        }

        $ids = DB::executeFirstColumn('SELECT id FROM comments WHERE ' . Comments::typeIdsMapToConditions($result) . " AND is_trashed = $not_trashed");

        if ($ids) {
            $result['Comment'] = $ids;
        }

        return $result;
    }

    /**
     * Replace one user with another user.
     *
     * @param array|null $additional
     */
    public function replaceMember(User $replace, User $with, $additional = null)
    {
        /** @var User $by */
        $by = isset($additional['by']) && $additional['by'] instanceof User ? $additional['by'] : null;

        if (empty($additional['by'])) {
            throw new InvalidParamError('additional[by]', $additional['by'], 'User expected');
        }

        try {
            DB::beginWork('Replacing member @ ' . __CLASS__);

            $with_is_already_a_member = $this->isMember($with);

            if (!$with_is_already_a_member) {
                $this->addMembers([$with], ['send_invitations' => false]);
            }

            if ($this->isLeader($replace)) {
                $this->setLeader($with);
            }

            // Update subscriptions and other assignees
            if ($parent_filter = $this->getProjectElementsParentFilter()) {
                $rememebered_subscriptions = $this->rememberUserSubscriptions($replace, $parent_filter);
                $this->replaceAssignmentsByUser($replace, $with, $by);
                $this->reassignUserSubscriptions($rememebered_subscriptions, $with);
            }

            $this->removeMembers([$replace], $additional);

            DB::commit('Member replaced @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to replace member @ ' . __CLASS__);

            throw $e;
        }

        // ---------------------------------------------------
        //  Send notifications if replacement has open
        //  assignment or replacement was not on the project
        //  already
        // ---------------------------------------------------

        if (array_var($additional, 'send_notification', true) && $with->getId() != $by->getId()) {
            if (DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM tasks WHERE project_id = ? AND assignee_id = ? AND completed_on IS NULL AND is_trashed = ? ORDER BY position', $this->getId(), $with->getId(), false)) {
                /** @var ReplacingProjectUserNotification $notification */
                $notification = AngieApplication::notifications()->notifyAbout(
                    'system/replacing_project_user',
                    $this,
                    $by,
                );

                $notification
                    ->setReplacingUser($replace)
                    ->sendToUsers($with);
            } else {
                if (!$with_is_already_a_member) {
                    AngieApplication::notifications()
                        ->notifyAbout('system/new_project', $this, $by)
                        ->sendToUsers($with);
                }
            }
        }
    }

    /**
     * Add user to this context.
     *
     * @param User[]|DBResult $users
     * @param array|null      $additional
     */
    public function addMembers($users, $additional = null)
    {
        if ($users && is_foreachable($users)) {
            parent::addMembers($users, $additional);

            if (array_var($additional, 'send_invitations', true)) {
                AngieApplication::notifications()
                    ->notifyAbout('system/new_project', $this, $this->getUpdatedBy())
                    ->sendToUsers($users);
            }

            DataObjectPool::announce(new ProjectMembershipGrantedEvent($this));
        }
    }

    public function setLeader(?User $leader): void
    {
        $this->setLeaderId($leader ? $leader->getId() : 0);
    }

    /**
     * Return parent filter for this project.
     *
     * @return string
     */
    private function getProjectElementsParentFilter()
    {
        if ($this->project_elements_parent_filter === false) {
            $this->project_elements_parent_filter = [
                Task::class => DB::executeFirstColumn('SELECT id FROM tasks WHERE project_id = ?', $this->getId()),
                RecurringTask::class => DB::executeFirstColumn('SELECT id FROM recurring_tasks WHERE project_id = ?', $this->getId()),
                TaskList::class => DB::executeFirstColumn('SELECT id FROM task_lists WHERE project_id = ?', $this->getId()),
                Discussion::class => DB::executeFirstColumn('SELECT id FROM discussions WHERE project_id = ?', $this->getId()),
                File::class => DB::executeFirstColumn('SELECT id FROM files WHERE project_id = ?', $this->getId()),
                Note::class => DB::executeFirstColumn('SELECT id FROM notes WHERE project_id = ?', $this->getId()),
            ];

            if ($this->project_elements_parent_filter[Task::class]) {
                $this->project_elements_parent_filter[Subtask::class] = DB::executeFirstColumn(
                    'SELECT id FROM subtasks WHERE task_id IN (?)',
                    $this->project_elements_parent_filter[Task::class],
                );

                if (empty($this->project_elements_parent_filter[Subtask::class])) {
                    unset($this->project_elements_parent_filter[Subtask::class]);
                }
            }

            $parents_by_type = [];

            foreach ($this->project_elements_parent_filter as $type => $ids) {
                if ($ids && !empty($ids)) {
                    $parents_by_type[$type] = DB::prepare('(`parent_type` = ? AND `parent_id` IN (?))', $type, $ids);
                }
            }

            $this->project_elements_parent_filter = implode(' OR ', $parents_by_type);
        }

        return $this->project_elements_parent_filter;
    }

    /**
     * Remember user subscriptions.
     *
     * @param  User   $user
     * @param  string $parent_filter
     * @return array
     */
    private function rememberUserSubscriptions($user, $parent_filter)
    {
        $result = [];

        if ($rows = DB::execute("SELECT parent_type, parent_id FROM subscriptions WHERE (user_id = ? OR user_email = ?) AND ($parent_filter)", $user->getId(), $user->getEmail())) {
            foreach ($rows as $row) {
                if (empty($result[$row['parent_type']])) {
                    $result[$row['parent_type']] = [];
                }

                $result[$row['parent_type']][] = $row['parent_id'];
            }
        }

        return count($result) ? $result : null;
    }

    /**
     * Replace $replace with $with.
     */
    private function replaceAssignmentsByUser(User $replace, User $with, User $by)
    {
        $log_values_batch = new DBBatchInsert('modification_log_values', ['modification_id', 'field', 'old_value', 'new_value']);

        $created_by_id = DB::escape($by->getId());
        $created_by_name = DB::escape($by->getName());
        $created_by_email = DB::escape($by->getEmail());

        if ($task_ids = DB::executeFirstColumn('SELECT id FROM tasks WHERE project_id = ? AND assignee_id = ?', $this->getId(), $replace->getId())) {
            foreach ($task_ids as $task_id) {
                DB::execute("INSERT INTO modification_logs (parent_type, parent_id, created_on, created_by_id, created_by_name, created_by_email) VALUES ('Task', ?, UTC_TIMESTAMP(), $created_by_id, $created_by_name, $created_by_email)", $task_id);
                $log_values_batch->insert(DB::lastInsertId(), 'assignee_id', serialize($replace->getId()), serialize($with->getId()));
            }
        }

        DB::execute("UPDATE tasks SET assignee_id = ?, delegated_by_id = ?, updated_on = UTC_TIMESTAMP(), updated_by_id = $created_by_id, updated_by_name = $created_by_name, updated_by_email = $created_by_email WHERE project_id = ? AND assignee_id = ?", $with->getId(), $by->getId(), $this->getId(), $replace->getId());

        $this->updateSubtaskAssignments($replace->getId(), $with->getId(), $by, $log_values_batch);
        $this->updateRecurringTasksAssignments($replace->getId(), $with->getId(), $by);

        $log_values_batch->done();
    }

    /**
     * Update subtask assignment information.
     *
     * @param int      $replace_user_id
     * @param int|null $with_user_id
     */
    private function updateSubtaskAssignments($replace_user_id, $with_user_id, User $by, DBBatchInsert &$log_values_batch)
    {
        // Remember that we removed assignees from subtasks
        if ($subtask_ids = DB::executeFirstColumn('SELECT subtasks.id FROM subtasks LEFT JOIN tasks ON subtasks.task_id = tasks.id WHERE tasks.project_id = ? AND subtasks.assignee_id = ?', $this->getId(), $replace_user_id)) {
            $by_id = DB::escape($by->getId());
            $by_name = DB::escape($by->getName());
            $by_email = DB::escape($by->getEmail());

            $task_ids = DB::executeFirstColumn('SELECT task_id FROM subtasks WHERE id IN (?)', $subtask_ids);

            foreach ($subtask_ids as $subtask_id) {
                DB::execute("INSERT INTO modification_logs (parent_type, parent_id, created_on, created_by_id, created_by_name, created_by_email) VALUES ('Subtask', ?, UTC_TIMESTAMP(), $by_id, $by_name, $by_email)", $subtask_id);
                $log_values_batch->insert(DB::lastInsertId(), 'assignee_id', $replace_user_id, $with_user_id);
            }

            if ($with_user_id) {
                DB::execute('UPDATE subtasks SET assignee_id = ?, delegated_by_id = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?) AND assignee_id = ?', $with_user_id, $by->getId(), $subtask_ids, $replace_user_id);
            } else {
                DB::execute("UPDATE subtasks SET assignee_id = '0', updated_on = UTC_TIMESTAMP() WHERE id IN (?) AND assignee_id = ?", $subtask_ids, $replace_user_id);
            }
            DB::execute('UPDATE tasks SET updated_on = UTC_TIMESTAMP(), updated_by_id = ?, updated_by_name = ?, updated_by_email = ? WHERE id IN (?)', $by->getId(), $by->getName(), $by->getEmail(), $task_ids);
        }

        Subtasks::clearCache();
        Tasks::clearCache();
    }

    /**
     * Reassign remembered subscriptions to another user.
     *
     * @param array $remembered_subscriptions
     * @param User  $to_user
     */
    private function reassignUserSubscriptions($remembered_subscriptions, $to_user)
    {
        if ($remembered_subscriptions) {
            $batch = new DBBatchInsert('subscriptions', ['parent_type', 'parent_id', 'user_id', 'user_name', 'user_email'], 50, DBBatchInsert::REPLACE_RECORDS);

            $escaped_user_id = DB::escape($to_user->getId());
            $escaped_user_name = DB::escape($to_user->getDisplayName());
            $escaped_user_email = DB::escape($to_user->getEmail());

            foreach ($remembered_subscriptions as $type => $ids) {
                $escaped_type = DB::escape($type);

                foreach ($ids as $id) {
                    $batch->insertEscapedArray([$escaped_type, DB::escape($id), $escaped_user_id, $escaped_user_name, $escaped_user_email]);
                }
            }

            $batch->done();
        }
    }

    /**
     * Remove user from this context.
     *
     * @param User[]|DBResult $users
     * @param array|null      $additional
     */
    public function removeMembers($users, $additional = null)
    {
        if (empty($additional['by']) || !($additional['by'] instanceof User)) {
            throw new InvalidParamError('additional[by]', $additional['by'], 'User expected');
        }

        try {
            DB::beginWork('Removing members @ ' . __CLASS__);

            parent::removeMembers($users);

            $revoked_user_ids = [];

            foreach ($users as $user) {
                $this->clearAssignmentsByUser($user, $additional['by']);
                $this->clearSubscriptionsByUser($user);
                $this->clearRemindersByUser($user);
                $this->clearUpdatesByUser($user);
                $revoked_user_ids[] = $user->getId();
                if ($this->isLeader($user)) {
                    $this->setLeader(null);
                    $this->save();
                }
            }

            DataObjectPool::announce(new ProjectMembershipRevokedEvent($this, $revoked_user_ids));

            DB::commit('Members removed @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to remove members @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Remove all assignments for a given user.
     */
    protected function clearAssignmentsByUser(User $user, User $by)
    {
        $parent_filter = $this->getProjectElementsParentFilter();

        if ($parent_filter) {
            $user_id = $user->getId();

            try {
                DB::beginWork('Clearing assignments by user @ ' . __CLASS__);

                $log_values_batch = new DBBatchInsert('modification_log_values', ['modification_id', 'field', 'old_value', 'new_value']);

                $created_by_id = DB::escape($by->getId());
                $created_by_name = DB::escape($by->getName());
                $created_by_email = DB::escape($by->getEmail());

                // Responsibilities
                if ($task_ids = DB::executeFirstColumn('SELECT id FROM tasks WHERE project_id = ? AND assignee_id = ?', $this->getId(), $user_id)) {
                    foreach ($task_ids as $task_id) {
                        DB::execute("INSERT INTO modification_logs (parent_type, parent_id, created_on, created_by_id, created_by_name, created_by_email) VALUES ('Task', ?, UTC_TIMESTAMP(), $created_by_id, $created_by_name, $created_by_email)", $task_id);
                        $log_values_batch->insert(DB::lastInsertId(), 'assignee_id', serialize($user_id), null);
                    }

                    DB::execute("UPDATE tasks SET assignee_id = '0', updated_on = UTC_TIMESTAMP(), updated_by_id = ?, updated_by_name = ?, updated_by_email = ? WHERE project_id = ? AND assignee_id = ?", $by->getId(), $by->getName(), $by->getEmail(), $this->getId(), $user_id);
                }

                $this->updateSubtaskAssignments($user_id, null, $by, $log_values_batch);
                $this->updateRecurringTasksAssignments($user_id, null, $by);

                $log_values_batch->done();

                DB::commit('Assignments cleared by user @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Failed to clear assignments by user @ ' . __CLASS__);

                throw $e;
            }

            Users::clearCache();
            Tasks::clearCache();
            Subtasks::clearCache();
        }
    }

    /**
     * Update recurring tasks and subtasks assignment information.
     *
     * @param int      $replace_user_id
     * @param int|null $with_user_id
     */
    private function updateRecurringTasksAssignments($replace_user_id, $with_user_id, User $by)
    {
        // need to select and foreach all recurring tasks from project because subtasks are sets into row_additional_properties
        if ($tasks = RecurringTasks::findBySQL('SELECT * FROM recurring_tasks WHERE project_id = ?', $this->getId())) {
            /** @var RecurringTask $task */
            foreach ($tasks as $task) {
                // recurring task assignment
                if ($task->getAssigneeId() === $replace_user_id) {
                    /** @var User $assignee */
                    $assignee = $with_user_id ? Users::findById($with_user_id) : null;
                    $task->setAssignee($assignee, $by); // re-assignee user from recurring task
                }

                // recurring subtasks assignments
                if ($subtasks = $task->getSubtasks()) {
                    $task->setSubtasks(null);
                    $new_subtasks = []; // new subtasks needs to be set

                    foreach ($subtasks as $subtask) {
                        $assignee_id = $subtask['assignee_id'];

                        if ($replace_user_id === $assignee_id) {
                            $assignee_id = $with_user_id ? $with_user_id : 0; // re-assignee user from recurring subtask
                        }

                        $new_subtasks[] = ['assignee_id' => $assignee_id, 'body' => $subtask['body']];
                    }

                    $task->setSubtasks($new_subtasks);
                    $task->save();
                }
            }
        }
    }

    /**
     * Remove all subscriptions for a given user.
     */
    protected function clearSubscriptionsByUser(User $user)
    {
        $parent_types = [
            Task::class => DB::executeFirstColumn('SELECT id FROM tasks WHERE project_id = ?', $this->getId()),
            RecurringTask::class => DB::executeFirstColumn('SELECT id FROM recurring_tasks WHERE project_id = ?', $this->getId()),
            Discussion::class => DB::executeFirstColumn('SELECT id FROM discussions WHERE project_id = ?', $this->getId()),
            Note::class => DB::executeFirstColumn('SELECT id FROM notes WHERE project_id = ?', $this->getId()),
        ];

        $table_names = [
            Task::class => Tasks::getTableName(),
            RecurringTask::class => RecurringTasks::getTableName(),
            Discussion::class => Discussions::getTableName(),
            Note::class => Notes::getTableName(),
        ];

        foreach ($parent_types as $parent_type => $parent_ids) {
            if ($parent_ids) {
                $parent_type_ids = DB::executeFirstColumn(
                    'SELECT DISTINCT parent_id FROM subscriptions WHERE parent_type = ? AND parent_id IN (?) AND user_id = ?',
                    $parent_type,
                    $parent_ids,
                    $user->getId(),
                );

                if ($parent_type_ids) {
                    DB::execute(
                        'DELETE FROM subscriptions WHERE user_id = ? AND parent_type = ? AND parent_id IN (?)',
                        $user->getId(),
                        $parent_type,
                        $parent_type_ids,
                    );

                    DB::execute(
                        sprintf(
                            'UPDATE %s SET updated_on = UTC_TIMESTAMP() WHERE id IN (?)',
                            $table_names[$parent_type],
                        ),
                        $parent_type_ids,
                    );

                    call_user_func(
                        [
                            Inflector::pluralize($parent_type),
                            'clearCacheFor',
                        ],
                        $parent_type_ids,
                    );
                }
            }
        }

        Projects::clearCacheFor([$this->getId()]);
    }

    /**
     * Remove all reminders for specific user.
     */
    public function clearRemindersByUser(User $user)
    {
        $reminders = Reminders::findBySQL(
            'SELECT r.* FROM reminders AS r LEFT JOIN tasks AS t ON r.parent_type = ? AND r.parent_id = t.id WHERE t.project_id = ?',
            Task::class,
            $this->getId(),
        );

        if ($reminders) {
            $type_ids_map = [];

            foreach ($reminders as $reminder) {
                if ($reminder->getCreatedById() === $user->getId()) {
                    $reminder->delete();
                } else {
                    if (empty($type_ids_map[$reminder->getType()])) {
                        $type_ids_map[$reminder->getType()] = [];
                    }

                    $type_ids_map[$reminder->getType()][] = $reminder->getId();
                }
            }

            if (count($type_ids_map)) {
                DB::execute(
                    sprintf(
                        'DELETE FROM subscriptions WHERE %s AND (user_id = ? OR user_email = ?)',
                        Subscriptions::typeIdsMapToConditions($type_ids_map),
                    ),
                    $user->getId(),
                    $user->getEmail(),
                );
            }
        }
    }

    public function clearUpdatesByUser(User $user)
    {
        $parent_filter = $this->getProjectElementsParentFilter();

        if ($parent_filter) {
            $notification_ids = DB::executeFirstColumn(
                'SELECT id FROM notifications WHERE (' . $parent_filter . ') OR (parent_type = ? AND parent_id = ?)',
                Project::class,
                $this->getId(),
            );

            if ($notification_ids) {
                Notifications::clearForRecipient($user, false, $notification_ids);

                DB::execute(
                    'DELETE FROM notifications WHERE
                        `id` IN (?) AND
                        NOT EXISTS (
                            SELECT * FROM `notification_recipients` WHERE
                                `notification_recipients`.`notification_id` = `notifications`.`id`
                        )
                    ',
                    $notification_ids,
                );
            }
        }
    }

    public function countResponsibilities(): array
    {
        $result = [];

        $member_ids = $this->getMemberIds();

        if (!empty($member_ids)) {
            $open_tasks_by_assignees = DB::execute(
                "SELECT assignee_id, COUNT(id) AS 'open_tasks_count' FROM tasks WHERE project_id = ? AND assignee_id IN (?) AND completed_on IS NULL AND is_trashed = ? GROUP BY assignee_id",
                $this->getId(),
                $member_ids,
                false,
            );

            if ($open_tasks_by_assignees) {
                foreach ($open_tasks_by_assignees as $open_tasks_by_assignee) {
                    $result[$open_tasks_by_assignee['assignee_id']] = (int) $open_tasks_by_assignee['open_tasks_count'];
                }
            }

            $open_subtasks_by_assignees = DB::execute(
                "SELECT subtasks.assignee_id, COUNT(subtasks.id) AS 'open_subtasks_count' FROM subtasks LEFT JOIN tasks ON subtasks.task_id = tasks.id WHERE tasks.project_id = ? AND subtasks.assignee_id IN (?) AND tasks.completed_on IS NULL AND tasks.is_trashed = ? AND subtasks.completed_on IS NULL AND subtasks.is_trashed = ? GROUP BY subtasks.assignee_id",
                $this->getId(),
                $member_ids,
                false,
                false,
            );

            if ($open_subtasks_by_assignees) {
                foreach ($open_subtasks_by_assignees as $open_subtasks_by_assignee) {
                    if (empty($result[$open_subtasks_by_assignee['assignee_id']])) {
                        $result[$open_subtasks_by_assignee['assignee_id']] = 0;
                    }

                    $result[$open_subtasks_by_assignee['assignee_id']] += (int) $open_subtasks_by_assignee['open_subtasks_count'];
                }
            }

            foreach ($member_ids as $member_id) {
                if (empty($result[$member_id])) {
                    $result[$member_id] = 0;
                }
            }

            ksort($result);
        }

        return $result;
    }

    /**
     * Revoke client access.
     *
     * @return Project
     */
    public function &revokeClientAccess(User $by)
    {
        DB::transact(function () use ($by) {
            if ($members = $this->getMembers()) {
                $revoke_access_to = [];

                foreach ($members as $member) {
                    if ($member instanceof Client) {
                        $revoke_access_to[] = $member;
                    }
                }

                if (count($revoke_access_to)) {
                    $this->removeMembers($revoke_access_to, ['by' => $by]);
                }
            }

            $this->setCompanyId(
                AngieApplication::getContainer()
                    ->get(OwnerCompanyResolverInterface::class)
                        ->getId(),
            );
            $this->save();
        }, 'Revoke client access');

        return $this;
    }

    // ---------------------------------------------------
    //  Trash
    // ---------------------------------------------------
    /**
     * Move to trash.
     *
     * @param bool $bulk
     */
    public function moveToTrash(User $by = null, $bulk = false)
    {
        try {
            DB::beginWork('Begin: move project to trash @ ' . __CLASS__);

            Notifications::deleteByParent($this);

            $this->moveProjectElementsToTrash($by, 'TaskLists', 'task_lists');
            $this->moveProjectElementsToTrash($by, 'Discussions', 'discussions');
            $this->moveProjectElementsToTrash($by, 'Notes', 'notes');
            $this->moveProjectElementsToTrash($by, 'Files', 'files');
            $this->moveProjectElementsToTrash($by, 'RecurringTasks', 'recurring_tasks');

            parent::moveToTrash($by, $bulk);
            DataObjectPool::announce(new ProjectMoveToTrashEvent($this));

            DB::commit('Done: move project to trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: move project to trash @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Bulk move project elements to trash.
     *
     * @param User|null $by
     * @param string    $manager_class
     * @param string    $table_name
     */
    private function moveProjectElementsToTrash($by, $manager_class, $table_name)
    {
        DB::execute("UPDATE $table_name SET original_is_trashed = ? WHERE project_id = ? AND is_trashed = ?", true, $this->getId(), true); // Remember original is_trashed flag for already trashed elements

        /** @var ITrash[] $elements */
        if ($elements = call_user_func("$manager_class::find", ['conditions' => ['project_id = ? AND is_trashed = ?', $this->getId(), false]])) {
            foreach ($elements as $element) {
                $element->moveToTrash($by, true);
            }
        }
    }

    /**
     * Restore from trash.
     *
     * @param bool $bulk
     */
    public function restoreFromTrash($bulk = false)
    {
        try {
            DB::beginWork('Begin: restore project from xtrash @ ' . __CLASS__);

            Notifications::deleteByParent($this);

            $this->restoreProjectElementsFromTrash('TaskLists', 'task_lists');
            $this->restoreProjectElementsFromTrash('Discussions', 'discussions');
            $this->restoreProjectElementsFromTrash('Notes', 'notes');
            $this->restoreProjectElementsFromTrash('Files', 'files');
            $this->restoreProjectElementsFromTrash('RecurringTasks', 'recurring_tasks');

            parent::restoreFromTrash($bulk);

            DB::commit('Done: restore project from trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: restore project from trash @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Bulk restore project elements from trash.
     *
     * @param string $manager_class
     * @param string $table_name
     */
    private function restoreProjectElementsFromTrash($manager_class, $table_name)
    {
        /** @var ITrash[] $elements */
        if ($elements = call_user_func("$manager_class::find", ['conditions' => ['project_id = ? AND is_trashed = ? AND original_is_trashed = ?', $this->getId(), true, false]])) {
            foreach ($elements as $element) {
                $element->restoreFromTrash(true);
            }
        }

        DB::execute("UPDATE $table_name SET is_trashed = ?, original_is_trashed = ? WHERE project_id = ? AND original_is_trashed = ?", true, false, $this->getId(), true); // Restore previously trashed elements as trashed
    }

    /**
     * Delete project and all related data.
     *
     * @param bool $bulk
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Deleting project @ ' . __CLASS__);

            $this->untouchable(
                function () {
                    TaskLists::deleteByProject($this);
                    Discussions::deleteByProject($this);
                    Tasks::deleteByProject($this);
                    Notes::deleteByProject($this);
                    Files::deleteByProject($this);
                    Stopwatches::deleteByProject($this);
                },
            );

            DB::execute('DELETE FROM `applied_project_templates` WHERE `project_id` = ?', $this->getId());

            parent::delete($bulk);

            DB::commit('Project deleted @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to delete project @ ' . __CLASS__);

            throw $e;
        }
    }

    // ---------------------------------------------------
    //  Invoice based on
    // ---------------------------------------------------

    /**
     * Run $callback while this object is untouchable.
     */
    public function untouchable(callable $callback)
    {
        $original_untouchable = $this->is_untouchable;

        $this->is_untouchable = true;

        call_user_func($callback);

        $this->is_untouchable = $original_untouchable;
    }

    /**
     * Return report result.
     *
     * @return TrackingFilter
     */
    public function prepareReportForInvoiceBasedOn()
    {
        $report = new TrackingFilter();

        $report->filterByProjects([$this->getId()]);
        $report->ungroup();

        return $report;
    }

    // ---------------------------------------------------
    //  Calendar
    // ---------------------------------------------------

    protected function getCalendarFeedElements(IUser $user)
    {
        /** @var DBResult|TaskList[] $task_lists */
        $task_lists = TaskLists::find([
            'conditions' => ['project_id = ? AND start_on IS NOT NULL AND due_on IS NOT NULL AND is_trashed = ?', $this->getId(), false],
            'order' => 'start_on',
        ]);

        $conditions = [DB::prepare('(project_id = ? AND start_on IS NOT NULL AND due_on IS NOT NULL AND is_trashed = ?)', $this->getId(), false)];

        if ($user instanceof User && $user->isClient()) {
            $conditions[] = DB::prepare('(is_hidden_from_clients = ?)', false);
        }

        /** @var DBResult|Task[] $tasks */
        $tasks = Tasks::find([
            'conditions' => implode(' AND ', $conditions),
            'order' => 'start_on',
        ]);

        /** @var DBResult|RecurringTask[] $recurring_tasks */
        $recurring_tasks = RecurringTasks::find([
            'conditions' => ['project_id = ? AND start_in IS NOT NULL AND due_in IS NOT NULL AND is_trashed = ?', $this->getId(), false],
        ]);

        $result = [];

        if (!empty($task_lists)) {
            $result = array_merge($result, $task_lists->toArray());
        }

        if (!empty($tasks)) {
            $result = array_merge($result, $tasks->toArray());
        }

        if (!empty($recurring_tasks)) {
            $result = array_merge($result, $recurring_tasks->toArray());
        }

        return $result;
    }

    public function getCalendarElementSummarySufix()
    {
        return ' (' . $this->getName() . ')';
    }

    // ---------------------------------------------------
    //  Touch
    // ---------------------------------------------------

    /**
     * Refresh object's updated_on flag.
     *
     * @param User|null  $by
     * @param array|null $additional
     * @param bool       $save
     */
    public function touch($by = null, $additional = null, $save = true)
    {
        if ($this->is_untouchable) {
            return;
        }

        $this->triggerEvent('on_before_touch', [$by, $additional, $save]);

        if ($this->getTouchUpdatesActivity()) {
            $this->setLastActivityOn(DateTimeValue::now());
        }

        if ($this instanceof IUpdatedOn) {
            $this->setUpdatedOn(DateTimeValue::now());
        }

        if ($this instanceof IUpdatedBy && $by instanceof IUser) {
            $this->setUpdatedBy($by);
        }

        if ($save) {
            $this->save();
        }

        $this->triggerEvent('on_after_touch', [$by, $additional, $save]);
    }

    /**
     * Returns $touch_updates_activity value.
     *
     * @return bool
     */
    public function getTouchUpdatesActivity()
    {
        return $this->touch_updates_activity;
    }

    /**
     * Marks that touch should update last_activity_on field.
     */
    public function touchUpdatesActivity()
    {
        $this->setTouchUpdatesActivity(true);
    }

    /**
     * Set $touch_updates_activity_value.
     *
     * @param bool $value true by default
     */
    private function setTouchUpdatesActivity($value = true)
    {
        $this->touch_updates_activity = $value;
    }

    /**
     * Marks that touch shouldn't update last_activity_on field.
     */
    public function touchDoesntUpdateActivity()
    {
        $this->setTouchUpdatesActivity(false);
    }

    /**
     * Should we include or ignore archived and trashed members (TRUE for include, FALSE for ignore).
     *
     * @return bool
     */
    protected function includeArchivedAndTrashedMembers()
    {
        return false;
    }

    /**
     * Make sure that we have this list in one place.
     *
     * @return array
     */
    protected function whatIsWorthRemembering()
    {
        return Projects::whatIsWorthRemembering();
    }

    protected function getSearchEngine()
    {
        return AngieApplication::search();
    }

    public function calculateCosts()
    {
        $projectId = $this->getId();

        $allTimeRecords = DB::executeFirstCell("
            SELECT SUM(costs.cost)
            FROM
              (SELECT (value * internal_rate) AS cost
               FROM time_records
               WHERE is_trashed = 0 AND 
                     (
                         (parent_type = 'Project' AND parent_id = ?) OR 
                         (parent_type='Task' AND parent_id IN (SELECT id FROM tasks WHERE project_id = ? AND is_trashed = 0))
                     )
              ) AS costs;",
            $projectId, $projectId,
        );

        $unbillableExpenses = DB::executeFirstCell("
            SELECT SUM(costs.value)
            FROM
              (SELECT value
               FROM expenses
               WHERE billable_status = 0 AND is_trashed = 0 AND ((parent_type = 'Project' AND parent_id = ?) OR (parent_type = 'Task' AND parent_id IN (SELECT id FROM tasks WHERE project_id = ? AND is_trashed = 0)))) AS costs;",
            $projectId, $projectId,
        );

        return $allTimeRecords + $unbillableExpenses;
    }

    public function calculateIncomes(): float
    {
        // if budget type is fixed then the income is the value of the budget
        if ($this->getBudgetType() === self::BUDGET_TYPE_FIXED) {
            return $this->getBudget();
        }

        // Income - TR(pending payment | paid | billable) * job_type_hourly_rate + EX(pp|paid|billable)
        $query = "
            SELECT SUM(incomes.income) FROM 
            (SELECT value * job_type_hourly_rate AS income
            FROM time_records 
            WHERE billable_status != 0 AND is_trashed != 1 AND 
                  (
                      (parent_type = 'Project' and parent_id = ?) OR 
                      (parent_type = 'Task' and parent_id IN (SELECT id FROM tasks WHERE project_id = ? AND is_trashed = 0))
                  )
            ) AS incomes";

        $time_records = DB::executeFirstCell($query, $this->getId(), $this->getId()) ?? 0;
        $query2 = "
            SELECT SUM(income.value)
            FROM
            (SELECT value
            FROM expenses
            WHERE billable_status != 0 AND is_trashed = 0 AND 
                  (
                      (parent_type = 'Project' AND parent_id = ?) OR 
                      (parent_type = 'Task' AND parent_id IN (SELECT id FROM tasks WHERE project_id = ? AND is_trashed = 0))
                  )) AS income;
            ";
        $expenses = DB::executeFirstCell($query2, $this->getId(), $this->getId()) ?? 0;

        return $time_records + $expenses;
    }

    public function checkIfThereAreRecordsWithoutInternalRate(): bool
    {
        $time_records_without_internal_rate = DB::executeFirstCell(
            'SELECT `id` FROM `time_records` 
            WHERE `billable_status` != 0 AND `is_trashed` != 1 AND `internal_rate` = 0 AND (
                (parent_type = ? and parent_id = ?) OR 
                (parent_type = ? and parent_id IN (SELECT id FROM tasks WHERE project_id = ? AND is_trashed = 0))
            )',
            Project::class,
            $this->getId(),
            Task::class,
            $this->getId(),
        ) ?? 0;

        return (bool) $time_records_without_internal_rate;
    }
}
