<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use Angie\Inflector;
use Emojione\Client as EmojiClient;
use Emojione\Ruleset;
use Michelf\Markdown;

class WrikeImporterIntegration extends AbstractImporterIntegration
{
    // constants
    const API_URL = 'https://www.wrike.com/api/v4';
    const API_AUTH = 'https://www.wrike.com/oauth2';
    const RESTRICTED_WRIKE_PLANS = ['Free', 'Premium'];
    const ROOT_FOLDER = 'WsRoot';
    const RB_FOLDER = 'RbRoot'; // Recycle bin folder
    const REQUEST_TIME_LIMIT = 60;
    const MAX_REQUEST_TIME_SLEEPING = 15;
    const MAX_REQUEST_ATTEMPTS = 10;
    const RESPONSE_ERROR_INVALID_FOLDER_ID = 'Invalid Folder ID';

    private $account_id;
    private $client_id;
    private $client_secret;
    private $access_token;
    private $http_client = false;
    private $client_plus_on = false;
    private $root_folders = [];
    private $custom_fields = [];
    private $mapping_table_name = 'wrike_migration_mappings';
    private $request_attempts = 0;

    public function isSingleton(): bool
    {
        return true;
    }

    /**
     * Return integration name.
     *
     * @return string
     */
    public function getName()
    {
        return 'Wrike Importer';
    }

    public function getShortName(): string
    {
        return 'wrike-importer';
    }

    protected function getLogEventPrefix(): string
    {
        return 'wrike';
    }

    /**
     * Get consumer key - get Wrike client id.
     *
     * @return string|null
     */
    public function getConsumerKey()
    {
        return defined('WRIKE_CONSUMER_KEY')
            ? WRIKE_CONSUMER_KEY
            : $this->getAdditionalProperty('consumer_key');
    }

    /**
     * Set consumer key - set Wrike client id.
     *
     * @param  string      $consumer_key
     * @return string|null
     */
    public function setConsumerKey($consumer_key)
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setAdditionalProperty('consumer_key', $consumer_key);
        }

        return $this->getConsumerKey();
    }

    /**
     * Get consumer secret key - get Wrike client secret key.
     *
     * @return string|null
     */
    public function getConsumerKeySecret()
    {
        return defined('WRIKE_CONSUMER_KEY_SECRET') ? WRIKE_CONSUMER_KEY_SECRET : $this->getAdditionalProperty('consumer_key_secret');
    }

    /**
     * Set consumer key secret - set Wrike client secret key.
     *
     * @param  string      $consumer_key_secret
     * @return string|null
     */
    public function setConsumerKeySecret($consumer_key_secret)
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setAdditionalProperty('consumer_key_secret', $consumer_key_secret);
        }

        return $this->getConsumerKeySecret();
    }

    /**
     * Get access token.
     *
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->getAdditionalProperty('access_token');
    }

    /**
     * Set access token.
     *
     * @param $access_token
     * @return mixed
     */
    public function setAccessToken($access_token)
    {
        return $this->setAdditionalProperty('access_token', $access_token);
    }

    /**
     * Get refresh token.
     *
     * @return string
     */
    private function getRefreshAccessToken()
    {
        return $this->getAdditionalProperty('refresh_token');
    }

    /**
     * Set refresh token.
     *
     * @param string $refresh_token
     */
    private function setRefreshToken($refresh_token)
    {
        $this->setAdditionalProperty('refresh_token', $refresh_token);
    }

    /**
     * Set credentials for Wrike.
     *
     * @param string $client_id
     * @param string $client_secret
     * @param string $access_token
     * @param string $account_id
     */
    public function setCredentials($client_id, $client_secret, $access_token, $account_id)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->access_token = $access_token;
        $this->account_id = $account_id;
    }

    /**
     * Set account ID for import.
     *
     * @param string $account_id
     */
    public function setAccountId($account_id)
    {
        $this->account_id = $account_id;
    }

    /**
     * Get Wrike accounts.
     *
     * @return mixed
     */
    private function getAccounts()
    {
        return $this->getAdditionalProperty('accounts', []);
    }

    /**
     * Get Account by ID.
     *
     * @param $account_id
     */
    private function getAccount($account_id)
    {
        $accounts = $this->getAccounts();
        foreach ($accounts as $account) {
            if ($account_id == $account['id']) {
                return $account;
            }
        }

        return null;
    }

    /**
     * Set Wrike accounts.
     *
     * @return mixed
     */
    private function setAccounts(array $accounts)
    {
        return $this->setAdditionalProperty('accounts', $accounts);
    }

    /**
     * Serialize integration.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'status' => $this->getStatus(),
            'import_progress' => $this->getImportProgress(),
            'import_total' => $this->getImportTotal(),
            'import_label' => $this->getImportLabel(),
            'is_wizard_supported' => $this->isWizardSupported(),
            'tasks_path' => ENVIRONMENT_PATH . '/tasks',
            'has_valid_access' => $this->hasValidAccess(),
            'request_url' => $this->getRequestUrl(),
            'accounts' => $this->getAccounts(),
        ]);
    }

    /**
     * Return true if access to wrike is valid.
     *
     * @return bool
     */
    public function hasValidAccess()
    {
        return $this->getAccessToken() && $this->getRefreshAccessToken();
    }

    /**
     * Get Request URL - authorize URL to Wrike API.
     *
     * @return string|null
     */
    public function getRequestUrl()
    {
        return !empty($this->getConsumerKey()) ? self::API_AUTH . "/authorize/v4?client_id={$this->getConsumerKey()}&redirect_uri={$this->getCallbackUrl()}&response_type=code&scope=amReadOnlyUser,wsReadOnly,amReadOnlyWorkflow,amReadOnlyGroup,amReadOnlyInvitation" : null;
    }

    /**
     * Get Callback Url - url where Wrike API send access token.
     *
     * @return string
     */
    private function getCallbackUrl()
    {
        return ROOT_URL . '/integrations/wrike-importer';
    }

    /**
     * Authorize wrike API user and set access token.
     *
     * @param  string                   $authorize_code
     * @return WrikeImporterIntegration
     */
    public function authorize($authorize_code)
    {
        $params = [
            'client_id' => $this->getConsumerKey(),
            'client_secret' => $this->getConsumerKeySecret(),
            'grant_type' => 'authorization_code',
            'code' => $authorize_code,
            'redirect_uri' => $this->getCallbackUrl(),
        ];

        $response = $this->makeAuthRequest('/token', $params, 'POST');

        $this->setAccessToken($response->access_token);
        $this->setRefreshToken($response->refresh_token);
        $this->save();

        return $this;
    }

    /**
     * Validate credentials.
     *
     * @return WrikeImporterIntegration
     * @throws Exception
     */
    public function validateCredentials()
    {
        try {
            $accounts = $this->getWrikeAccounts();
        } catch (Exception $e) {
            throw new Exception('Authentication Failed');
        }

        $this->setAccounts($accounts);
        $this->save();

        return $this;
    }

    /**
     * Do refresh token.
     *
     * @param  $response
     * @return bool
     */
    public function doRefreshToken($response)
    {
        return $this->getAccessToken() && $this->getRefreshAccessToken() && $response->error == 'not_authorized';
    }

    /**
     * Refresh access token.
     */
    private function refreshAccessToken()
    {
        $params = [
            'client_id' => $this->getConsumerKey(),
            'client_secret' => $this->getConsumerKeySecret(),
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getRefreshAccessToken(),
            'redirect_uri' => $this->getCallbackUrl(),
        ];

        $response = $this->makeAuthRequest('/token', $params, 'POST');

        $this->setAccessToken($response->access_token);
        $this->setRefreshToken($response->refresh_token);

        $this->save();
    }

    /**
     * Schedule import account.
     *
     * @return $this
     */
    public function &scheduleImport()
    {
        $command_arguments = [
            $this->getConsumerKey(),
            $this->getConsumerKeySecret(),
            $this->getAccessToken(),
            $this->account_id,
        ];

        $this->dispatchJob(
            [
                'command' => 'import_wrike_account',
                'command_arguments' => $command_arguments,
                'log_output_to_file' => AngieApplication::getAvailableWorkFileName('wrike-import-' . date('Y-m-d H-i-s'), 'txt', null),
            ]
        );

        // setting status to pending
        $this->setStatus(self::STATUS_PENDING);
        $this->save();

        return $this;
    }

    private $import_output;

    public function startImport(callable $output = null)
    {
        parent::startImport($output);

        $this->import_output = $output;

        $this->migrateMappingTable($this->mapping_table_name);

        /** @var ClientPlusIntegration $client_plus_integration */
        $client_plus_integration = Integrations::findFirstByType(ClientPlusIntegration::class);
        $this->client_plus_on = $client_plus_integration->isInUse();

        try {
            $this->startRequestBatch(DateTimeValue::now()->getTimestamp());

            $account = $this->getAccount($this->account_id);

            $custom_fields = [];
            if (!in_array($account['plan'], self::RESTRICTED_WRIKE_PLANS)) {
                $custom_fields = $this->makeGetRequest('/customfields');
            }

            $this->setCustomFileds($custom_fields);
            $this->importUsersAndGroups();

            $this->setStatus(self::STATUS_IMPORTING);
            $this->save();

            $this->importWorkfolows();
            $this->importFolders();

            $this->setStatus(self::STATUS_INVITING);
            $this->save();
        } catch (Exception $e) {
            $this->setStatus(self::STATUS_FAILED);
            $this->save();

            throw new Exception('ERROR: ' . $e);
        }
    }

    public function &startOver()
    {
        $this->setAccounts([]);
        $this->setAccessToken(null);
        $this->setRefreshToken(null);

        return parent::startOver();
    }

    //-----------------------------------------------------
    // GET OBJECTS
    //-----------------------------------------------------

    /**
     * Get all wrike accounts.
     *
     * @return array
     */
    private function getWrikeAccounts()
    {
        $accounts = [];

        $wrike_accounts = $this->makeGetRequest('/account?fields=["subscription"]');

        if (!empty($wrike_accounts)) {
            foreach ($wrike_accounts as $account) {
                $accounts[] = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'plan' => $account->subscription->type,
                ];
            }
        }

        return $accounts;
    }

    /**
     * Set root folders.
     */
    public function setRootFolders(array $root_folders)
    {
        $this->root_folders = $root_folders;
    }

    /**
     * Get root folders.
     *
     * @return array
     */
    public function getRootFolders()
    {
        return $this->root_folders;
    }

    //-----------------------------------------------------
    // IMPORT METHODS
    //-----------------------------------------------------

    /**
     * Import users & groups.
     *
     * @throws Exception
     */
    private function importUsersAndGroups()
    {
        $this->outputBuffer($this->import_output, [
            "****************************************************************\r\n",
            "***** Importing Wrike User AND Groups ...                  *****\r\n",
            "****************************************************************\r\n",
        ]);

        $this->outputBuffer($this->import_output, "Importing Users & Temas ... \r\n\n");

        $group_ids = [];
        $peoples = [];

        $response_contacts = $this->makeGetRequest('/contacts');

        // Contacts (Users & Groups)
        foreach ($response_contacts as $contact) {
            if ($contact->type === 'Person') {
                $peoples[$contact->id] = $contact->id;
            } elseif ($contact->type === 'Group') {
                $group_ids[$contact->id] = $contact->id;
            } else {
                AngieApplication::log()->warning('Wrike users & groups: There is no type like {type}', [
                    'type' => $contact->type,
                ]);
            }
        }

        $this->setImportTotal(count($peoples) + count($group_ids));
        $this->setImportProgress();
        $this->setImportLabel(' Users & Groups for ');
        $this->save();

        $i = 1;
        // Import Peoples
        foreach ($peoples as $people) {
            $this->importUser($people);
            $this->setImportProgress($i);
            $this->save();
            ++$i;
        }

        // Import Temas
        foreach ($group_ids as $group_id) {
            $this->importTeam($group_id);
            $this->setImportProgress($i);
            $this->save();
            ++$i;
        }
    }

    /**
     * Import users.
     *
     * @param  string        $wrike_user_id
     * @return DbResult|User
     */
    public function importUser($wrike_user_id)
    {
        $user = $this->makeGetRequest("/users/{$wrike_user_id}")[0];

        // Check if user profile belongs to account
        $wrike_user_profile = false;
        if (!empty($user)) {
            foreach ($user->profiles as $profile) {
                if ($profile->accountId == $this->account_id) {
                    $wrike_user_profile = $profile;
                }
            }
        }

        if ($wrike_user_profile) {
            if (empty($wrike_user_profile->email) && $user->deleted) {
                $first_name = trim($user->firstName, '.');
                $last_name = trim($user->lastName, '.');

                $wrike_user_profile->email = "{$first_name}.{$last_name}.{$user->id}@example.com";
                $wrike_user_profile->email = str_replace(' ', '', Inflector::transliterate(mb_strtolower($wrike_user_profile->email, 'UTF-8')));
            }
        } else {
            return null;
        }

        $ac_user = Users::findByEmail($wrike_user_profile->email, true);

        if (!$ac_user instanceof User) {
            $temporary_file = $this->downloadAttachment($user->avatarUrl);

            if (is_file($temporary_file)) {
                $avatar_location = AngieApplication::storeFile($temporary_file);
                @unlink($temporary_file);
            } else {
                $avatar_location = '';
            }

            [$user_role, $user_custom_permissions] = $this->defineUserRole($wrike_user_profile);

            $params = [
                'type' => $user_role,
                'custom_permissions' => $user_custom_permissions,
                'email' => $wrike_user_profile->email,
                'first_name' => $user->firstName,
                'last_name' => $user->lastName,
                'company_id' => 0,
                'password' => AngieApplication::authentication()->generateStrongPassword(32),
                'avatar_location' => $avatar_location,
                'is_trashed' => !empty($user->deleted) ? true : false,
            ];

            $ac_user = Users::create($params, true, false);

            // disable morning mail for all members
            ConfigOptions::setValueFor(['notifications_user_send_morning_paper' => false], $ac_user);
        }

        $this->mapObject($this->mapping_table_name, $user->id, $ac_user->getId(), 'User');

        return $ac_user;
    }

    /**
     * Import teams.
     *
     * @param $group_id
     */
    public function importTeam($group_id)
    {
        $group = $this->makeGetRequest("/groups/{$group_id}")[0];

        $ac_team = $this->getMappedObjectType($this->mapping_table_name, $group->id, 'Team');

        if (empty($ac_team) && !empty($group->memberIds)) {
            $duplicated_team = Teams::findOne(
                [
                    'conditions' => ['name = ?', $group->title],
                ]
            );

            if ($duplicated_team instanceof Team) {
                $group->title = "{$group->title}-{$group_id}";
            }

            $created_team = Teams::create(
                [
                    'name' => $this->maxLength($group->title),
                ]
            );

            if ($created_team) {
                $team_users_batch = new DBBatchInsert('team_users', ['team_id', 'user_id'], 500, DBBatchInsert::REPLACE_RECORDS);

                foreach ($group->memberIds as $member_id) {
                    $team_users_batch->insert(
                        $created_team->getId(),
                        $this->getUser($member_id, true)->getId()
                    );
                }

                $this->mapObject($this->mapping_table_name, $group->id, $created_team->getId(), 'Team');

                $team_users_batch->done();
            }
        }
    }

    /**
     * Import workflows like labels.
     */
    private function importWorkfolows()
    {
        $workflows = $this->makeGetRequest('/workflows');
        $colors = $this->getWrikeColors();

        foreach ($workflows as $workflow) {
            foreach ($workflow->customStatuses as $item) {
                $label = Labels::find(['conditions' => ['name=? AND type=?', $item->name, Label::class]]);
                if (!$label instanceof Label && !$ac_label = $this->getMappedObjectType($this->mapping_table_name, $item->id, 'Label')) {
                    $label = Labels::create([
                        'type' => 'TaskLabel',
                        'name' => $item->name,
                        'color' => isset($colors[$item->color]) ? $colors[$item->color] : null,
                        'is_global' => true,
                    ]);

                    $this->mapObject($this->mapping_table_name, $item->id, $label->getId(), 'Label');
                }
            }
        }
    }

    /**
     * Import folders.
     */
    private function importFolders()
    {
        $this->outputBuffer($this->import_output, [
            "****************************************************************\r\n",
            "***** Importing Wrike Folders ...                          *****\r\n",
            "****************************************************************\r\n",
        ]);

        $folders = $this->makeGetRequest('/folders');

        $this->setImportTotal(count($folders) - 1);
        $this->setImportProgress(0);
        $this->save();

        foreach ($folders as $folder) {
            if ($folder->scope == self::ROOT_FOLDER) {
                $this->importRootFolder($folder);
            }
        }
    }

    /**
     * Import ROOT folder for current user.
     *
     * @param $wrike_folder
     */
    public function importRootFolder($wrike_folder)
    {
        $this->outputBuffer($this->import_output, "Importing ROOT Project: {$wrike_folder->title}\r\n");

        $account = $this->getAccount($this->account_id);
        $me = $this->getMe(true);

        $project = $this->getMappedObjectType($this->mapping_table_name, $wrike_folder->id, 'Project');

        if (!($project instanceof Project)) {
            $creator = Users::findFirstOwner();

            $project = Projects::create(
                [
                    'name' => $this->maxLength("{$account['name']} - {$wrike_folder->title}"),
                    'body' => lang('This is ROOT folder and here is shared data for all users sorted in task lists by email'),
                    'company_id' => AngieApplication::getContainer()
                        ->get(OwnerCompanyResolverInterface::class)
                            ->getId(),
                    'created_by_id' => $creator->getId(),
                    'created_by_name' => $creator->getFirstName() . ' ' . $creator->getLastName(),
                    'created_by_email' => $creator->getEmail(),
                    'send_invitations' => $this->send_emails,
                    'leader_id' => $creator->getId(),
                    'skip_default_task_list' => true,
                ],
                true,
                false
            );

            $task_list = TaskLists::findOneBy('name', $me->getEmail());
            $folder_tasks = $this->getFolderTasksIds($wrike_folder->id);

            if (!empty($folder_tasks) && !$task_list instanceof TaskList) {
                $task_list = TaskLists::create(
                    [
                        'project_id' => $project->getId(),
                        'name' => $this->maxLength($me->getEmail()),
                    ],
                    true,
                    false
                );

                $this->importTasks($folder_tasks, $task_list);
            }

            $this->mapObject($this->mapping_table_name, $wrike_folder->id, $project->getId(), 'Project');
        }

        foreach ($wrike_folder->childIds as $folder_id) {
            $this->importFolder($folder_id);
        }
    }

    /**
     * Import folder like project or task-list.
     *
     * @param string           $wrike_project_id
     * @param Project/TaskList $parent
     * @param string           $wrike_parent_id
     */
    private function importFolder($wrike_project_id, $parent = null, $wrike_parent_id = null)
    {
        $wrike_folder = $this->makeGetRequest("/folders/{$wrike_project_id}")[0];

        if (!empty($wrike_folder->project)) {
            $this->outputBuffer($this->import_output, "Importing Project: {$wrike_folder->title}\r\n");
        } else {
            $this->outputBuffer($this->import_output, "Importing Folder: {$wrike_folder->title}\r\n");
        }

        $this->setImportProgress($this->getImportProgress() + 1);
        $this->setImportLabel($wrike_folder->title);
        $this->save();

        if ($this->isTaskList($wrike_folder) && $parent != null) {
            if (!empty($wrike_folder)) {
                $this->importTaskList($wrike_folder, $parent, $wrike_parent_id);
            }
        } else {
            $this->importProject($wrike_folder, $parent);
        }
    }

    /**
     * Import folder like project.
     *
     * @param $wrike_folder
     * @param Project/TaskList $parent
     */
    public function importProject($wrike_folder, $parent = null)
    {
        $project = $this->getMappedObjectType($this->mapping_table_name, $wrike_folder->id, 'Project');

        if (!($project instanceof Project)) {
            [$creator, $leader, $project_members] = $this->getFolderMembers($wrike_folder);

            if (!$creator instanceof User) {
                $creator = Users::findFirstOwner();
            }

            $project_description = '';
            if ($parent instanceof TaskList || $parent instanceof Project) {
                $project_description = lang('In Wrike this was child folder of folder: :project_name', ['project_name' => $parent->getName()]);
            }

            $project_params = [
                'name' => $this->maxLength($wrike_folder->title),
                'body' => $project_description,
                'company_id' => AngieApplication::getContainer()
                    ->get(OwnerCompanyResolverInterface::class)
                        ->getId(),
                'created_on' => $wrike_folder->createdDate,
                'created_by_id' => $creator->getId(),
                'created_by_name' => $creator->getFirstName() . ' ' . $creator->getLastName(),
                'created_by_email' => $creator->getEmail(),
                'send_invitations' => $this->send_emails,
                'leader_id' => $leader instanceof User ? $leader->getId() : $creator->getId(),
                'skip_default_task_list' => true,
            ];

            $project = Projects::create($project_params, true, false);

            if ($project instanceof Project) {
                $project->addMembers($project_members, ['send_invitations' => $this->send_emails]);

                $task_list = TaskLists::create(
                    [
                        'project_id' => $project->getId(),
                        'name' => 'Inbox',
                        'created_on' => $wrike_folder->createdDate,
                    ],
                    true,
                    false
                );

                $wrike_inline_images = $this->getInlineImages($wrike_folder);

                [$project_attachments, $comments_attachments, $inline_images] = $this->getAttachments('folders', $wrike_folder->id, $wrike_inline_images);

                $this->importNote($project, $wrike_folder, $inline_images);
                $this->importFilesToProject($project, $project_attachments);
                $this->importDiscussions($project, $wrike_folder, $comments_attachments, $inline_images);

                $this->importTasks($this->getFolderTasksIds($wrike_folder->id), $task_list);
                $this->mapObject($this->mapping_table_name, $wrike_folder->id, $project->getId(), 'Project');
            }
        }

        foreach ($wrike_folder->childIds as $childId) {
            $this->importFolder($childId, $project, $wrike_folder->id);
        }
    }

    /**
     * Import folder like task-list.
     *
     * @param                  $wrike_folder
     * @param Project/TaskList $parent
     * @param string           $wrike_parent_id
     */
    private function importTaskList($wrike_folder, $parent, $wrike_parent_id)
    {
        /** @var TaskList $task_list */
        $task_list = $this->getMappedObjectType($this->mapping_table_name, $wrike_parent_id . '-' . $wrike_folder->id, 'TaskList');

        if (!$task_list instanceof TaskList) {
            [$creator, $leader, $members] = $this->getFolderMembers($wrike_folder);

            if (!empty($new_project_members)) {
                $task_list->getProject()->addMembers($new_project_members, ['send_invitations' => $this->send_emails]);
            }

            $name = $wrike_folder->title;
            $project_id = $parent->getId();

            if ($parent instanceof TaskList) {
                $project_id = $parent->getProjectId();
                $name = $parent->getName() . '\\' . $wrike_folder->title;

                if (strlen($name) >= 150) {
                    $name = $wrike_folder->title;
                }
            }

            $task_list = TaskLists::create(
                [
                    'project_id' => $project_id,
                    'name' => $this->maxLength($name),
                    'created_on' => $wrike_folder->createdDate,
                ],
                true,
                false
            );

            $this->updateProjectMembers($task_list->getProject(), $members);
            $this->importTasks($this->getFolderTasksIds($wrike_folder->id), $task_list);

            if ($task_list instanceof TaskList) {
                $this->mapObject($this->mapping_table_name, $wrike_parent_id . '-' . $wrike_folder->id, $task_list->getId(), 'TaskList');
            }
        }

        foreach ($wrike_folder->childIds as $childId) {
            if (!empty($childId)) {
                $this->importFolder($childId, $task_list, $wrike_folder->id);
            }
        }
    }

    /**
     * Import tasks.
     */
    private function importTasks(array $task_ids, TaskList $task_list, Task $parent = null)
    {
        if (is_foreachable($task_ids)) {
            $total_tasks = count($task_ids);

            if ($total_tasks >= 99) {
                $task_ids_chunks = array_chunk($task_ids, 99);
            } else {
                $task_ids_chunks[] = $task_ids;
            }

            foreach ($task_ids_chunks as $task_ids_chunk) {
                $ids = implode(',', $task_ids_chunk);
                $tasks = $this->makeGetRequest("/tasks/{$ids}?fields=['recurrent','attachmentCount']");

                foreach ($tasks as $task) {
                    if ($parent instanceof Task && $this->isSubtask($task)) {
                        $this->importSubtask($task, $parent);
                    } else {
                        $this->importTask($task, $task_list, $parent);
                    }
                }
            }
        }
    }

    /**
     * Import task.
     *
     * @param $task
     */
    private function importTask($task, TaskList $task_list, Task $parent = null)
    {
        [$creator, $assignee, $subscribers] = $this->getTaskMembers($task);

        $this->updateProjectMembers($task_list->getProject(), $subscribers);

        if ($parent instanceof Task) {
            $task->description = '<p><b>In Wrike this was subtask of task <a href="' . $parent->getViewUrl() . '">' . $parent->getName() . '</a></b><p/>' . $task->description;
        }

        if (!empty($task->customFields)) {
            $fields_description = null;

            foreach ($task->customFields as $custom_field) {
                $field = $this->getCustomField($custom_field->id);

                if ($field) {
                    $fields_description .= "<ul>
                        <li><b>Name:</b> {$field->title}</li>
                        <li><b>Type:</b> {$field->type}</li>
                        <li><b>Value: </b> {$custom_field->value}</li>
                    </ul><br/>";
                }
            }

            if (!empty($fields_description)) {
                $task->description .= '<h3>Custom Fileds:</h3>' . $fields_description;
            }
        }

        $wrike_inline_images = $this->getInlineImages($task);
        [$task_attachments, $comments_attachments, $inline_images] = $this->getAttachments('tasks', $task->id, $wrike_inline_images);

        if (!empty($inline_images)) {
            $task->description = $this->importInlineImages($task->description, $inline_images);
        }

        $task_params = [
            'name' => $this->maxLength($task->title),
            'body' => Markdown::defaultTransform($task->description),
            'task_list_id' => $task_list->getId(),
            'assignee_id' => $assignee instanceof User ? $assignee->getId() : null,
            'project_id' => $task_list->getProjectId(),
            'created_on' => $task->createdDate,
            'created_by_id' => $creator->getId(),
            'created_by_name' => $creator->getFirstName() . ' ' . $creator->getLastName(),
            'created_by_email' => $creator->getEmail(),
            'updated_on' => new DateTimeValue($task->updatedDate),
            'start_on' => !empty($task->dates->start) ? new DateTimeValue($task->dates->start) : null,
            'due_on' => !empty($task->dates->due) ? new DateTimeValue($task->dates->due) : null,
            'is_important' => $task->importance == 'High' ? true : false,
            'notify_subscribers' => $this->send_emails,
        ];

        $label = $this->getMappedObjectType($this->mapping_table_name, $task->customStatusId, 'Label');

        if ($label instanceof TaskLabel) {
            $task_params['labels'] = [$label->getName()];
        }

        if ($task->status == 'Completed') {
            $task_params['completed_on'] = new DateTimeValue($task->updatedDate);
            $task_params['completed_by_id'] = $creator->getId();
            $task_params['completed_by_name'] = $creator->getFirstName() . ' ' . $creator->getLastName();
            $task_params['completed_by_email'] = $creator->getEmail();
        }

        /** @var Tasks $task */
        $ac_task = Tasks::create($task_params, false, false);
        $this->saveTask($ac_task);
        $ac_task->setSubscribers($subscribers);

        $this->importAttachments($ac_task, $task_attachments);
        $this->importTimelogs($ac_task, $task->id);
        $this->importComments('tasks', $ac_task, $task->id, $comments_attachments);

        if (count($task->subTaskIds) > 0) {
            $this->importTasks($task->subTaskIds, $task_list, $ac_task);
        }
    }

    /**
     * Import subtask.
     *
     * @param $wrike_subtask
     */
    private function importSubtask($wrike_subtask, Task $parent)
    {
        [$creator, $assignee, $subscribers] = $this->getTaskMembers($wrike_subtask);

        $this->updateProjectMembers($parent->getProject(), $subscribers);

        $subtask_parameters = [
            'task_id' => $parent->getId(),
            'body' => $wrike_subtask->title,
            'assignee_id' => $assignee instanceof User ? $assignee->getId() : null,
            'notify_assignee' => $this->send_emails,
        ];

        if ($wrike_subtask->status == 'Completed' || $parent->isCompleted()) {
            if ($wrike_subtask->status == 'Completed') {
                $subtask_parameters['completed_on'] = new DateTimeValue($wrike_subtask->updatedDate);
                $subtask_parameters['completed_by_id'] = $creator->getId();
                $subtask_parameters['completed_by_name'] = $creator->getFirstName() . ' ' . $creator->getLastName();
                $subtask_parameters['completed_by_email'] = $creator->getEmail();
            } else {
                $subtask_parameters['completed_on'] = $parent->getCompletedOn();
                $subtask_parameters['completed_by_id'] = $parent->getCompletedById();
                $subtask_parameters['completed_by_name'] = $parent->getCompletedByName();
                $subtask_parameters['completed_by_email'] = $parent->getCompletedByEmail();
            }
        }

        Subtasks::create($subtask_parameters, true, false);
    }

    /**
     * Import timelogs.
     *
     * @param int $wrike_task_id
     */
    private function importTimelogs(Task $task, $wrike_task_id)
    {
        $time_logs = $this->makeGetRequest("/tasks/{$wrike_task_id}/timelogs");

        if (is_foreachable($time_logs)) {
            $job_type_id = JobTypes::getDefaultId();

            foreach ($time_logs as $time_log) {
                // don't use first owner instead
                if ($user = $this->getUser($time_log->userId)) {
                    TimeRecords::create(
                        [
                            'parent_type' => get_class($task),
                            'parent_id' => $task->getId(),
                            'job_type_id' => $job_type_id,
                            'summary' => $time_log->comment,
                            'record_date' => DateValue::makeFromString($time_log->trackedDate),
                            'value' => $time_log->hours,
                            'user_id' => $user->getId(),
                            'user_name' => $user->getDisplayName(),
                            'user_email' => $user->getEmail(),
                            'billable_status' => true,
                            'created_by_id' => $user->getId(),
                            'created_by_name' => $user->getDisplayName(),
                            'created_by_email' => $user->getEmail(),
                            'notify_subscribers' => $this->send_emails,
                        ],
                        true,
                        false
                    );
                }
            }
        }
    }

    /**
     * Import Note for project (This is project/folder description).
     *
     * @param $wrike_folder
     * @param $inline_images
     */
    private function importNote(Project $project, $wrike_folder, $inline_images, Project $parent = null)
    {
        if (!empty($wrike_folder->customFields)) {
            $fields_description = null;

            foreach ($wrike_folder->customFields as $custom_field) {
                $field = $this->getCustomField($custom_field->id);

                if ($field) {
                    $fields_description .= "<ul>
                        <li><b>Name:</b> {$field->title}</li>
                        <li><b>Type:</b> {$field->type}</li>
                        <li><b>Value: </b> {$custom_field->value}</li>
                    </ul></br>";
                }
            }

            if (!empty($fields_description)) {
                $wrike_folder->description .= '<h3>Custom Fileds:</h3>' . $fields_description;
            }
        }

        if (!empty($inline_images)) {
            $wrike_folder->description = $this->importInlineImages($wrike_folder->description, $inline_images);
        }

        if ($wrike_folder->description) {
            Notes::create([
                'project_id' => $project->getId(),
                'name' => $this->maxLength(lang('Description for project: :project_name', ['project_name' => $project->getName()])),
                'body' => $wrike_folder->description,
                'created_on' => $project->getCreatedOn(),
                'created_by_id' => $project->getCreatedById(),
                'notify_subscribers' => $this->send_emails,
            ]);
        }
    }

    /**
     * Import project discussion.
     *
     * @param $wrike_folder
     * @param $comments_attachments
     * @param $inline_images
     */
    private function importDiscussions(Project $project, $wrike_folder, $comments_attachments, $inline_images)
    {
        $comments = $this->makeGetRequest("/folders/{$wrike_folder->id}/comments");

        if (!empty($comments)) {
            $discussion = Discussions::create([
                'project_id' => $project->getId(),
                'name' => $this->maxLength(lang('This is comments for project: :project_name', ['project_name' => $project->getName()])),
                'body' => trim(lang('<h3>Discussion based on project</h3>')),
                'notify_subscribers' => $this->send_emails,
            ]);

            if ($discussion instanceof Discussion) {
                $this->importComments('folders', $discussion, $wrike_folder->id, $comments_attachments);
            }
        }
    }

    /**
     * Import comments.
     *
     * @param string $type
     * @param string $wrike_object_id
     * @param array  $attachments
     */
    private function importComments($type, IComments $parent, $wrike_object_id, $attachments)
    {
        $wrike_comments = $this->makeGetRequest("/{$type}/{$wrike_object_id}/comments");

        if (is_foreachable($wrike_comments)) {
            foreach ($wrike_comments as $comment) {
                $user = $this->getUser($comment->authorId);
                $additional_params = [];
                $formatted_attachments = [];

                $additional_params['created_on'] = new DateTimeValue($comment->createdDate);
                $additional_params['notify_subscribers'] = $this->send_emails;

                // client for handle emoji
                $client = new EmojiClient(new Ruleset());
                $client->imageType = 'svg';

                $body = $comment->text ? Markdown::defaultTransform($client->toImage($comment->text)) : '<i>No comment</i>';
                if (!$user instanceof User) {
                    $wrike_user = $this->makeGetRequest("/users/{$comment->authorId}");

                    if (!empty($wrike_user[0])) {
                        $this->outputBuffer($this->import_output, ["User is found {$wrike_user[0]->firstName} {$wrike_user[0]->lastName}, {$comment->authorId}, comment_id: {$comment->id}"]);
                    }

                    $user = Users::findByEmail('unknown@exmaple.com');

                    if (!$user instanceof Users) {
                        $user = new AnonymousUser('Unknown', 'unknown@example.com');
                    }
                }

                try {
                    $ac_comment = $parent->submitComment($body, $user, $additional_params);

                    if (isset($attachments[$comment->id]) && is_foreachable($attachments[$comment->id])) {
                        foreach ($attachments[$comment->id] as $attachment) {
                            $path = $this->downloadAttachment($attachment->url);

                            $formatted_attachments[] = [
                                'path' => $path,
                                'filename' => $this->maxLength($attachment->name),
                                'type' => $attachment->contentType,
                            ];
                        }

                        if ($ac_comment instanceof Comment && is_foreachable($formatted_attachments)) {
                            $ac_comment->attachFilesFromArray($formatted_attachments); //@todo should include "created_on" and "created_by" additional params

                            foreach ($formatted_attachments as $formated_attachment) {
                                @unlink($formated_attachment['path']); //unlink tmp files
                            }
                        }
                    }
                } catch (Exception $e) {
                    AngieApplication::log()->warning('Comment skipped during Wrike import', [
                        'comment_body' => $body,
                        'exception' => $e,
                    ]);
                }
            }
        }
    }

    /**
     * Import files to project.
     */
    private function importFilesToProject(Project $project, array $attachments)
    {
        if (is_foreachable($attachments)) {
            foreach ($attachments as $attachment) {
                if (!empty($attachment->commentId)) {
                    continue;
                }

                $path = $this->downloadAttachment($attachment->url);
                $file_name = $attachment->name ? $this->maxLength($attachment->name) : 'file_name';

                $type = is_null($attachment->contentType) ? 'application/octet-stream' : $attachment->contentType;

                $uploaded_file = UploadedFiles::addFile($path, $file_name, $type, false);

                $creator = $this->getUser($attachment->authorId, true);

                /* @var File $new_file */
                $file = Files::create([
                    'type' => LocalFile::class,
                    'project_id' => $project->getId(),
                    'name' => $file_name,
                    'body' => '',
                    'created_on' => $attachment->createdDate,
                    'created_by_id' => $creator->getId(),
                    'uploaded_file_code' => $uploaded_file->getCode(),
                    'is_hidden_from_clients' => empty($uploaded_file->private) ? 0 : 1,
                ]);

                /** @var WarehouseIntegration $warehouse_integration */
                $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);

                if ($warehouse_integration->isInUse()) {
                    $job = new \ActiveCollab\ActiveCollabJobs\Jobs\Instance\UploadLocalFileToWarehouse([
                        'instance_id' => AngieApplication::getAccountId(),
                        'instance_type' => 'feather',
                        'tasks_path' => ENVIRONMENT_PATH . '/tasks',
                        'access_token' => $warehouse_integration->getAccessToken(),
                        'store_id' => $warehouse_integration->getStoreId(),
                        'local_file_id' => $file->getId(),
                    ]);

                    AngieApplication::jobs()->dispatch($job);
                }

                @unlink($path);
            }
        }
    }

    /**
     * Import inline attachments.
     *
     * @param  string $wrike_description
     * @return string
     */
    private function importInlineImages($wrike_description, array $attachments)
    {
        foreach ($attachments as $attachment) {
            $wrike_file = $this->downloadAttachment($attachment->url);

            [$target_path, $location] = AngieApplication::storeFile($wrike_file);

            $uploaded_file = UploadedFiles::create([
                'name' => $this->maxLength($attachment->name),
                'mime_type' => 'image/png',
                'size' => filesize($wrike_file),
                'location' => $location,
                'md5' => md5_file($target_path),
            ]);

            if ($uploaded_file instanceof UploadedFile) {
                $new_img = '<img src=/' . ltrim(str_replace(ROOT_URL, '', $uploaded_file->getThumbnailUrl()), '/') . " name='{$uploaded_file->getLocation()}' original_file_name='{$uploaded_file->getName()}'  alt='{$uploaded_file->getName()}' image-type='attachment' object-id='{$uploaded_file->getCode()}'/>";
                $wrike_description = str_replace($attachment->img_html, $new_img, $wrike_description);
            }

            @unlink($wrike_file);
        }

        return $wrike_description;
    }

    /**
     * Import attachments.
     *
     * @return array
     */
    private function importAttachments(IAttachments $parent, array $attachments)
    {
        if (is_foreachable($attachments)) {
            foreach ($attachments as $attachment) {
                $filename = $this->maxLength($attachment->name);
                $path = $this->downloadAttachment($attachment->url);

                if (file_exists($path)) {
                    $type = is_null($attachment->contentType) ? 'application/octet-stream' : $attachment->contentType;
                    $parent->attachFile(
                        $path,
                        $filename,
                        $type,
                        $this->getUser($attachment->authorId, true)
                    );

                    @unlink($path);
                } else {
                    $this->outputBuffer($this->import_output, [
                        "----------------------------------------------------------------\r\n",
                        "FILE: {$filename} DOESNT EXISTS ON WRIKE\r\n",
                        "----------------------------------------------------------------\r\n",
                    ]);
                }
            }
        }
    }

    /**
     * Send users invite.
     *
     * @return $this
     */
    public function invite()
    {
        return $this->inviteUsers($this->mapping_table_name);
    }

    //-----------------------------------------------------
    // INDENTIFIRES
    //-----------------------------------------------------

    /**
     * Is folder task-list.
     *
     * @param $wrike_folder
     * @return bool
     */
    private function isTaskList($wrike_folder)
    {
        if (in_array($wrike_folder->scope, [self::ROOT_FOLDER, self::RB_FOLDER])) {
            return false;
        }

        foreach ($wrike_folder->parentIds as $parentId) {
            if (in_array($parentId, $this->getRootFolders())) {
                return false;
            }
        }

        if (!empty($wrike_folder->project)) {
            return false;
        }

        if ($wrike_folder->hasAttachments) {
            return false;
        }

        if (!empty($wrike_folder->description)) {
            return false;
        }

        if (!empty($wrike_folder->customFields)) {
            return false;
        }

        return true;
    }

    /**
     * Check Task if look like subtask.
     *
     * @param $wrike_task
     * @return bool
     */
    private function isSubtask($wrike_task)
    {
        if (!empty($wrike_task->subtaskIds)) {
            return false;
        }

        if (!empty($wrike_task->description)) {
            return false;
        }

        if ($wrike_task->hasAttachments) {
            return false;
        }

        if (!empty($wrike_task->customFields)) {
            return false;
        }

        $time_log = $this->makeGetRequest("/tasks/{$wrike_task->id}/timelogs");
        if (!empty($time_log)) {
            return false;
        }

        return true;
    }

    //-----------------------------------------------------
    // HELPERS
    //-----------------------------------------------------

    /**
     * Update project members.
     */
    public function updateProjectMembers(Project $project, array $members)
    {
        $new_project_members = [];

        if (!empty($members)) {
            /* @var User $subscriber */
            foreach ($members as $member) {
                if (!in_array($member->getId(), $project->getMemberIds())) {
                    $new_project_members[] = $member;
                }
            }
        }

        if (!empty($new_project_members)) {
            $project->addMembers($new_project_members, ['send_invitations' => $this->send_emails]);
        }
    }

    /**
     * Get project members (members, leader).
     *
     * @param $wrike_folder
     * @return array
     */
    private function getFolderMembers($wrike_folder)
    {
        $leader = null;
        $creator = null;
        $members = [];

        foreach ($wrike_folder->sharedIds as $shared_id) {
            $user = $this->getUser($shared_id);
            if ($user instanceof User) {
                $members[$user->getId()] = $user;
            } else {
                /** @var Team $team */
                $team = $this->getMappedObjectType($this->mapping_table_name, $shared_id, 'Team');
                if ($team instanceof Team) {
                    $team_members = $team->getActiveMembers();

                    if (!empty($team_members)) {
                        foreach ($team_members as $team_member) {
                            $members[$team_member->getId()] = $team_member;
                        }
                    }
                }
            }
        }

        if (!empty($wrike_folder->project->ownerIds)) {
            foreach ($wrike_folder->project->ownerIds as $owner_id) {
                $user = $this->getUser($owner_id);

                if ($user instanceof User) {
                    $members[$user->getId()] = $user;

                    if ($leader != null && $user->canManageTasks()) {
                        $leader = $user->getId();
                    }
                }
            }
        }

        if (!$leader instanceof User && !empty($wrike_folder->project->ownerIds)) {
            $leader = $this->predefineClientUser($wrike_folder->project->ownerIds);
        }

        if (!empty($wrike_folder->project->authorId)) {
            $creator = $this->getUser($wrike_folder->project->authorId, true);
        }

        return [$creator, $leader, $members];
    }

    /**
     * Get task members (subscribers, assignee, creator).
     *
     * @param $task
     * @return array
     */
    private function getTaskMembers($task)
    {
        $assignee = null;
        $creator = null;
        $subscribers = [];

        $member_categories = [
            'followers' => $task->followerIds,
            'shared' => $task->sharedIds,
            'authors' => $task->authorIds,
            'responsible' => $task->responsibleIds,
        ];

        foreach ($member_categories as $member_category_name => $member_category) {
            foreach ($member_category as $member_id) {
                $user = $this->getUser($member_id);

                if ($user instanceof User) {
                    $subscribers[$user->getId()] = $user;

                    if ($member_category_name == 'responsible' && !$assignee instanceof User && $user->canManageTasks()) {
                        $assignee = $user;
                    }

                    if ($member_category_name == 'authors' && !($creator instanceof User)) {
                        $creator = $user;
                    }
                } else {
                    if ($member_category_name == 'shared') {
                        /** @var Team $team */
                        $team = $this->getMappedObjectType($this->mapping_table_name, $member_id, 'Team');

                        if ($team instanceof Team) {
                            $team_members = $team->getActiveMembers();

                            if (!empty($team_members)) {
                                foreach ($team_members as $team_member) {
                                    $subscribers[$team_member->getId()] = $team_member;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$assignee instanceof User && !empty($member_categories['responsible'])) {
            $assignee = $this->predefineClientUser($member_categories['responsible']);
        }

        if (!($creator instanceof User)) {
            $creator = Users::findFirstOwner();
        }

        return [$creator, $assignee, $subscribers];
    }

    /**
     * Predefine users for assignee or leader for Task or Project.
     *
     * @param $wrike_users
     * @return User|null
     */
    private function predefineClientUser($wrike_users)
    {
        if (!empty($wrike_users) && is_array($wrike_users)) {
            $user = $this->getUser($wrike_users[0]);

            if ($user instanceof Client) {
                if (!$this->client_plus_on) {
                    /** @var ClientPlusIntegration $client_plus_integration */
                    $client_plus_integration = Integrations::findFirstByType(ClientPlusIntegration::class);
                    $client_plus_integration->enable();
                    $this->client_plus_on = true;
                }

                $user = Users::changeUserType($user, Client::class, [Client::CAN_MANAGE_TASKS], Users::findFirstOwner());

                if ($user->canManageTasks()) {
                    return $user;
                }
            }
        }

        return null;
    }

    /**
     * Get tasks IDs from folder.
     *
     * @param $folder_id
     * @return array
     */
    private function getFolderTasksIds($folder_id)
    {
        $tasks = $this->makeGetRequest("/folders/{$folder_id}/tasks");

        $task_ids = [];

        if (is_foreachable($tasks)) {
            foreach ($tasks as $task) {
                $task_ids[] = $task->id;
            }
        }

        return $task_ids;
    }

    /**
     * Get Wrike attachments.
     *
     * @param string $type (Folder, Task)
     * @param string $id
     * @parms array  $inline_images
     * @return array|null
     */
    private function getAttachments($type, $id, array $wrike_inline_images = null)
    {
        $attachments = $this->makeGetRequest("/{$type}/{$id}/attachments?withUrls=true&versions=true");

        $comment_attachments = [];
        $object_attachments = [];
        $inline_images = [];
        if (is_foreachable($attachments)) {
            foreach ($attachments as $attachment) {
                if ($attachment->type != 'Wrike') {
                    continue;
                }

                if (empty($attachment->commentId)) {
                    $url_segments = explode(':', $attachment->url);
                    $url_segments = end($url_segments);
                    $wrike_url_id = explode('/', $url_segments)[0];

                    if (array_key_exists($wrike_url_id, $wrike_inline_images)) {
                        $attachment->img_html = $wrike_inline_images[$wrike_url_id];
                        $inline_images[$wrike_url_id] = $attachment;
                    } else {
                        $object_attachments[] = $attachment;
                    }
                } else {
                    $comment_attachments[$attachment->commentId][] = $attachment;
                }
            }
        }

        return [$object_attachments, $comment_attachments, $inline_images];
    }

    /**
     * Get inline images from body.
     *
     * @param $wrike_task
     * @return array
     */
    private function getInlineImages($wrike_task)
    {
        $wrike_inline_images = [];

        if (strpos($wrike_task->description, '<img ') !== false) {
            $doc = new DOMDocument();
            $doc->loadHTML($wrike_task->description);

            $tags = $doc->getElementsByTagName('img');

            foreach ($tags as $tag) {
                $old_src = $tag->getAttribute('src');
                $image_chunk = explode('/', $old_src);
                array_pop($image_chunk);
                $image_id = end($image_chunk);
                $img_html = $tag->ownerDocument->saveXML($tag);
                $wrike_inline_images[$image_id] = str_replace('/>', ' />', $img_html);
            }
        }

        return $wrike_inline_images;
    }

    /**
     * Get colors from Wrike.
     *
     * @return array
     */
    private function getWrikeColors()
    {
        $wrike_colors = $this->makeGetRequest('/colors');

        $colors = [];
        foreach ($wrike_colors as $color) {
            $colors[$color->name] = $color->hex;
        }

        return $colors;
    }

    /**
     * Define AC Roles by Wrike roles.
     *
     * @param $user_profile
     * @return array
     */
    public function defineUserRole($user_profile)
    {
        $custom_permissions = [];
        if (!empty($user_profile->role) && $user_profile->role == 'Collaborator') {
            $role_name = Client::class;
        } else {
            if (!empty($user_profile->owner)) {
                $role_name = Owner::class;
            } elseif (!empty($user_profile->admin)) {
                $role_name = Member::class;
                $custom_permissions = [User::CAN_MANAGE_PROJECTS];
            } elseif (!empty($user_profile->external)) {
                $role_name = Client::class;
                $custom_permissions = [User::CAN_MANAGE_TASKS];

                if ($this->client_plus_on == false) {
                    /** @var ClientPlusIntegration $client_plus_integration */
                    $client_plus_integration = Integrations::findFirstByType(ClientPlusIntegration::class);
                    $client_plus_integration->enable();
                    $this->client_plus_on = true;
                }
            } else {
                $role_name = Member::class;
            }
        }

        return [$role_name, $custom_permissions];
    }

    /**
     * Check to see if we imported user already, if not import him.
     *
     * @param $wrike_user_id
     * @return DbResult|object|Owner|null
     */
    private function getUser($wrike_user_id, bool $use_first_owner_instead = false)
    {
        $user = null;

        if (!empty($wrike_user_id)) {
            $user = $this->getMappedObjectType($this->mapping_table_name, $wrike_user_id, 'User');
        }

        if (!$user && $use_first_owner_instead) {
            $user = Users::findFirstOwner();
        }

        return $user ?? null;
    }

    /**
     * Get my user.
     *
     * @return User
     */
    public function getMe(bool $use_first_owner_instead = false)
    {
        $me = $this->makeGetRequest('/contacts?me=true')[0];

        return $this->getUser($me->id, $use_first_owner_instead);
    }

    /**
     * Set Custom Fields.
     *
     * @param $custom_fields
     */
    private function setCustomFileds($custom_fields)
    {
        if (!empty($custom_fields) && is_array($custom_fields)) {
            foreach ($custom_fields as $custom_field) {
                $this->custom_fields[$custom_field->id] = $custom_field;
            }
        }
    }

    /**
     * Get Custom Field.
     *
     * @param $custom_field_id
     * @return mixed
     */
    private function getCustomField($custom_field_id)
    {
        return isset($this->custom_fields[$custom_field_id]) ? $this->custom_fields[$custom_field_id] : [];
    }

    //-----------------------------------------------------
    // REQUESTS (AUTH, POST, GET, CURL)
    //-----------------------------------------------------

    /**
     * Make AUTH Request.
     *
     * @param  string $url_path
     * @param  array  $params
     * @param  string $method
     * @return mixed
     */
    private function makeAuthRequest($url_path, $params, $method = 'GET')
    {
        return $this->makeCurlRequest(self::API_AUTH . $url_path, $params, $method);
    }

    /**
     * Make POST Request.
     *
     * @param  string $url_path
     * @return mixed
     */
    private function makePostRequest($url_path, array $params = [])
    {
        $response = $this->makeCurlRequest(self::API_URL . $url_path, $params, 'POST');

        return $response;
    }

    /**
     * Make GET Request.
     *
     * @param  string $url_path
     * @return mixed
     */
    private function makeGetRequest($url_path, array $params = [])
    {
        try {
            $response = $this->makeCurlRequest(self::API_URL . $url_path, $params);

            if ($response && !empty($response->data)) {
                return $response->data;
            } else {
                $this->outputBuffer(
                    $this->import_output,
                    "Skiping import due to empty response from url '". self::API_URL . $url_path ."' \r\n\n"
                );
            }
        } catch (Exception $e) {
            $this->outputBuffer(
                $this->import_output,
                "Error while trying to import from url '" . self::API_URL . $url_path . "'  ... \r\n\n"
            );
        }
    }

    /**
     * Make CURL Request.
     *
     * @param  string $url
     * @param  string $method
     * @param  bool   $recursive
     * @return mixed
     */
    private function makeCurlRequest($url, array $params, $method = 'GET', $recursive = false)
    {
        if ($method === 'GET' && count($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        if ($this->getAccessToken()) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->getAccessToken(),
            ]);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // On dev server only!

        $result = json_decode(curl_exec($ch));

        $info = curl_getinfo($ch);

        curl_close($ch);
        unset($ch);

        $response_code = $info['http_code'];

        if ($this->getAccessToken() && $response_code == 401) {
            if ($recursive && ($this->request_attempts >= self::MAX_REQUEST_ATTEMPTS)) {
                throw new RuntimeException("Cannot refresh authorization token ERROR: {$result->error}");
            } else {
                $this->request_attempts++;
                $this->refreshAccessToken();
                $this->makeCurlRequest($url, $params, $method, true);
            }
        } elseif ($response_code == 429 || $response_code == 503) {
            sleep(min($this->getRequestBatchTime(self::REQUEST_TIME_LIMIT), self::MAX_REQUEST_TIME_SLEEPING));

            if ($response_code == 503) {
                if ($this->request_attempts >= self::MAX_REQUEST_ATTEMPTS) {
                    throw new RuntimeException('503 Service unavailable');
                }

                $this->request_attempts++;
            }

            $this->makeCurlRequest($url, $params, $method, true);
        } elseif ($response_code == 400) {
            if (!empty($result->errorDescription)) {
                if ($result->errorDescription === WrikeImporterIntegration::RESPONSE_ERROR_INVALID_FOLDER_ID) {
                    $this->outputBuffer($this->import_output, "Skip import folder, Invalid Folder ID ... \r\n\n");

                    return [];
                }
            }
        } elseif ($response_code == 401) {
            throw new RuntimeException('Not Authorized');
        } elseif ($response_code !== 200) {
            throw new RuntimeException("Invalid code or HTTPS {$response_code}");
        }

        if ($recursive) {
            $this->startRequestBatch(DateTimeValue::now()->getTimestamp());
            $this->request_attempts = 0;
        }

        return $result;
    }

    /**
     * Download a single attachment file.
     *
     * @param  string $url
     * @return string
     */
    private function downloadAttachment($url)
    {
        if ($this->http_client === false) {
            $this->http_client = new GuzzleHttp\Client();
        }

        $ac_tmp_attachments = WORK_PATH . '/' . AngieApplication::getAccountId() . '-wrike_attachments';
        recursive_mkdir($ac_tmp_attachments);

        $ext = '.png';

        $filename = $ac_tmp_attachments . '/ac_attachment_' . make_string() . $ext;

        // if client not initialized, initialize it now
        if ($this->http_client === false) {
            $this->http_client = new GuzzleHttp\Client();
        }

        // request options
        $request_options = [];

        // custom headers
        $request_options['headers'] = [
            'User-Agent' => 'Active Collab (' . self::API_CONTACT . ')',
        ];

        // curl options
        $request_options['config'] = [
            'curl' => [
                CURLOPT_MAX_RECV_SPEED_LARGE => 5242880, // 5MB per second
            ],
        ];

        // output response to file
        $request_options['save_to'] = $filename;

        // execute request
        $this->http_client->get($url, $request_options);

        return $filename;
    }
}
