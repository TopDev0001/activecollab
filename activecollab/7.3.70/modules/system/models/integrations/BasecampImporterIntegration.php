<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\UploadLocalFileToWarehouse;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;

class BasecampImporterIntegration extends AbstractImporterIntegration
{
    // constants
    const API_URL = 'https://basecamp.com';
    const API_VERSION = 'v1';

    private string $mapping_table_name = 'basecamp_migration_mappings';

    /**
     * Username credential.
     *
     * @var string
     */
    private $username;

    /**
     * Password credential.
     *
     * @var string
     */
    private $password;

    /**
     * Application credential.
     *
     * @var string
     */
    private $account_id;

    /**
     * Http Client.
     *
     * @var GuzzleHttp\Client
     */
    private $http_client = false;

    /**
     * @return string
     */
    public function getName()
    {
        return 'Basecamp Importer';
    }

    public function getShortName(): string
    {
        return 'basecamp-importer';
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

    protected function getLogEventPrefix(): string
    {
        return 'basecamp';
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
        ]);
    }

    /**
     * Set basecamp credentials.
     *
     * @param string $username
     * @param string $password
     * @param string $account_id
     */
    public function setCredentials($username, $password, $account_id)
    {
        $this->username = $username;
        $this->password = $password;
        $this->account_id = $account_id;
    }

    /**
     * Validate supplied credentials.
     *
     * @return array
     * @throws Exception
     */
    public function validateCredentials()
    {
        // try to fetch user info
        try {
            $logged_user = $this->makeRequest('/people/me');
        } catch (Exception $e) {
            throw new Exception('Authentication Failed');
        }

        // check if user is admin
        if (!$logged_user->admin) {
            throw new Exception(lang('The credentials you provided are not for admin user'));
        }

        $logged_user_id = $logged_user->id;
        $logged_user_projects = $this->makeRequest('/people/' . $logged_user_id . '/projects');

        $archived_count = 0;
        $template_count = 0;
        $draft_count = 0;
        $active_count = 0;

        if (count($logged_user_projects)) {
            foreach ($logged_user_projects as $project) {
                if ($project->archived) {
                    ++$archived_count;
                } else {
                    if ($project->draft) {
                        ++$draft_count;
                    } else {
                        if ($project->template) {
                            ++$template_count;
                        } else {
                            ++$active_count;
                        }
                    }
                }
            }
        }

        return [
            'name' => $logged_user->name,
            'email_address' => $logged_user->email_address,
            'is_admin' => $logged_user->admin,
            'active_projects' => $active_count,
            'archived_projects' => $archived_count,
            'template_projects' => $template_count,
            'draft_count' => $draft_count,
        ];
    }

    /**
     * Make request to basecamp API.
     *
     * @param  string $route
     * @param  array  $params
     * @return mixed
     */
    private function makeRequest($route, $params = null)
    {
        // if client not initialized, initialize it now
        if ($this->http_client === false) {
            $this->http_client = new GuzzleHttp\Client();
        }

        // detect if we have to assemble route or we have it already
        if (strpos($route, 'http') === 0) {
            $request_url = $route;
        } else {
            $request_url = self::API_URL . '/' . $this->account_id . '/api/' . self::API_VERSION . ($route[0] == '/' ? '' : '/') . $route . '.json';
        }

        // if there additional params to the url
        if ($params) {
            $request_url .= '?' . http_build_query($params);
        }

        // request options
        $request_options = [];

        // authorization
        $request_options['auth'] = [$this->username, $this->password];

        // custom headers
        $request_options['headers'] = [
            'User-Agent' => 'Active Collab (' . self::API_CONTACT . ')',
        ];

        // execute request
        $response = $this->http_client->get($request_url, $request_options);

        // return the response
        return json_decode($response->getBody());
    }

    /**
     * Schedule import process.
     *
     * @return BasecampImporterIntegration
     */
    public function &scheduleImport()
    {
        $this->dispatchJob(
            [
                'command' => 'import_basecamp_account',
                'command_arguments' => [$this->account_id, $this->username, $this->password],
                'log_output_to_file' => AngieApplication::getAvailableWorkFileName('basecamp-import-' . date('Y-m-d H-i-s'), 'txt'),
            ]
        );

        // setting status to pending
        $this->setStatus(self::STATUS_PENDING);
        $this->save();

        return $this;
    }

    public function startImport(callable $output = null)
    {
        parent::startImport($output);

        // update bc mapping table if exists
        $this->migrateMappingTable($this->mapping_table_name);

        // import all projects
        $this->importProjects($output);

        return true;
    }

    // ---------------------------------------------------
    //  Process
    // ---------------------------------------------------

    /**
     * Do import all projects.
     *
     * @throws Exception
     */
    private function importProjects(callable $output)
    {
        // set status to importing projects
        $this->setStatus(self::STATUS_IMPORTING);
        $this->save();

        // get all projects
        $projects = array_merge((array) $this->makeRequest('/projects'), (array) $this->makeRequest('/projects/archived'));

        // check if maybe we don't have projects
        if (!count($projects)) {
            $this->setStatus(self::STATUS_FAILED);
            $this->save();

            throw new Exception('There are no projects in this Basecamp account');
        }

        $this->setImportTotal(count($projects));
        $this->setImportProgress(0);
        $this->save();

        $this->outputBuffer($output, [
            "****************************************************************\r\n",
            '***** There is ' . count($projects) . " active project(s) to import. Importing ... *****\r\n",
            "****************************************************************\r\n",
        ]);

        $this->outputBuffer($output, 'Importing people...');
        $this->insertProjectsPeoples($projects);
        $this->outputBuffer($output, "People are imported.\r\n");

        // import projects one by one
        $counter = 1;
        foreach ($projects as $project) {
            $this->setImportProgress($counter);
            $this->setImportLabel($project->name);
            $this->save();

            if (!$this->getMappedProject($project->id)) {
                $this->outputBuffer($output, [
                    "----------------------------------------- ONE PROJECT\r\n",
                    "Importing project: '" . $project->name . "'\r\n",
                ]);

                $this->importProject($project->id, $output);

                $this->outputBuffer($output, [
                    "Project '" . $project->name . "' is imported.\r\n",
                    "----------------------------------------- END OF ONE PROJECT\r\n",
                ]);
            } else {
                if ($output) {
                    $this->outputBuffer($output, "Project '" . $project->name . "' already imported. Skipped. ...\r\n");
                }
            }

            ++$counter;
        }

        $this->setStatus(self::STATUS_INVITING);
        $this->save();
    }

    /**
     * Initially Insert All peoples to AC first.
     *
     * @param $projects
     */
    private function insertProjectsPeoples($projects)
    {
        $to_insert_peoples = [];
        $should_turn_on_client_plus = false;

        if (is_foreachable($projects)) {
            foreach ($projects as $project) {
                $bc_peoples = $this->getProjectPeopleByProjectId($project->id);
                if (is_foreachable($bc_peoples)) {
                    foreach ($bc_peoples as $bc_user) {
                        if (!array_key_exists($bc_user->id, $to_insert_peoples)) {
                            $role = $bc_user->is_client ? 'Client' : 'Member';
                            $to_insert_peoples[$bc_user->id]['user'] = $bc_user;
                            $to_insert_peoples[$bc_user->id]['role'] = $role;
                        } else {
                            if ($to_insert_peoples[$bc_user->id]['role'] != 'PowerClient') {
                                if (($to_insert_peoples[$bc_user->id]['role'] == 'Client' && !$bc_user->is_client) || ($to_insert_peoples[$bc_user->id]['role'] == 'Member' && $bc_user->is_client)) {
                                    $to_insert_peoples[$bc_user->id]['role'] = 'PowerClient';

                                    if ($should_turn_on_client_plus == false) {
                                        $should_turn_on_client_plus = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Add Users
            if (!empty($to_insert_peoples)) {
                if ($should_turn_on_client_plus) {
                    /** @var ClientPlusIntegration $client_plus_integration */
                    $client_plus_integration = Integrations::findFirstByType(ClientPlusIntegration::class);
                    if (!$client_plus_integration->isInUse()) {
                        $client_plus_integration->enable();
                    }
                }

                foreach ($to_insert_peoples as $user) {
                    $this->importUser($user['user'], $user['role']);
                }
            }
        }
    }

    /**
     * Return mapped project instance by Basecamp project ID.
     *
     * @param  int          $bc_project_id
     * @return Project|null
     */
    private function getMappedProject($bc_project_id)
    {
        if ($project_id = DB::executeFirstCell('SELECT ac_object_id FROM ' . $this->getMappingTableName($this->mapping_table_name) . ' WHERE object_type = ? AND tool_object_id = ?', 'Project', $bc_project_id)) {
            return DataObjectPool::get('Project', $project_id);
        }

        return null;
    }

    /**
     * Import project by Id.
     *
     * @param int $bc_project_id
     */
    public function importProject($bc_project_id, callable $output = null)
    {
        if ($bc_project = $this->getProjectById($bc_project_id)) {
            $creator = $this->getBasecampUser($bc_project->creator->id); //get creator

            $project_members = [$creator->getId()];

            if ($bc_project->accesses->count > 0) {
                $bc_people_to_add = $this->getProjectPeopleByProjectId($bc_project->id);

                if (is_foreachable($bc_people_to_add)) {
                    foreach ($bc_people_to_add as $bc_user) {
                        $project_member_id = $this->getBasecampUser($bc_user->id)->getId();

                        if ($project_member_id && !in_array($project_member_id, $project_members)) {
                            $project_members[] = $project_member_id;
                        }
                    }
                }
            }

            $created_project = Projects::create(
                [
                    'name' => $this->maxLength($bc_project->name),
                    'body' => $bc_project->description,
                    'company_id' => AngieApplication::getContainer()
                        ->get(OwnerCompanyResolverInterface::class)
                            ->getId(),
                    'created_on' => $bc_project->created_at,
                    'created_by_id' => $creator->getId(),
                    'members' => $project_members,
                    'send_invitations' => $this->send_emails,
                    'skip_default_task_list' => true,
                ],
                true,
                false
            );

            $this->outputBuffer($output, 'Get project "To Do Lists"... ');
            $this->addToDoListsToProject($created_project, $bc_project);
            $this->outputBuffer($output, "---> Project 'To Do Lists' are imported.\r\n");

            $this->outputBuffer($output, 'Get project discussion... ');
            $this->addDiscussionsToProjects($created_project, $bc_project, $output);
            $this->outputBuffer($output, "---> Project discussion are imported.\r\n");

            $this->outputBuffer($output, 'Get files... ');
            $this->addFilesToProjects($created_project, $bc_project);
            $this->outputBuffer($output, "Files are imported.\r\n");

            $this->outputBuffer($output, 'Get text documents... ');
            $this->addTextDocumentsToProjects($created_project, $bc_project);
            $this->outputBuffer($output, "Text documents are imported.\r\n");

            if ($bc_project->starred) {
                Favorites::addToFavorites($created_project, $creator);
            }

            if ($bc_project->archived == true) {
                $this->completeObject($created_project, $creator, new DateTimeValue($bc_project->updated_at), true);
            }

            $this->mapProject($bc_project_id, $created_project->getId());
        }
    }

    /**
     * Return project data from basecamp by project_id.
     *
     * @param $project_id
     * @return mixed
     */
    private function getProjectById($project_id)
    {
        return $this->makeRequest('/projects/' . $project_id);
    }

    /**
     * Check to see if we imported creator already, if not import him.
     *
     * @param  object|int $bc_user_id
     * @return User
     */
    private function getBasecampUser($bc_user_id)
    {
        if (is_object($bc_user_id)) {
            $bc_user_id = $bc_user_id->id;
        }

        $user = $this->getMappedUser($bc_user_id);

        if ($user) {
            return $user;
        }

        try {
            $user = $this->importUser($this->getUserById($bc_user_id));
        } catch (Exception $e) {
            $user = Users::findFirstOwner(); // In case that it is example project created by basecamp team
        }

        return $user;
    }

    /**
     * Return mapped user instance by Basecamp user ID.
     *
     * @param  int                  $bc_user_id
     * @return DataObject|User|null
     */
    private function getMappedUser($bc_user_id)
    {
        $ac_object_id = DB::executeFirstCell(
            sprintf(
                'SELECT ac_object_id FROM %s WHERE object_type = ? AND tool_object_id = ?',
                $this->getMappingTableName($this->mapping_table_name)
            ),
            User::class,
            $bc_user_id
        );

        if ($ac_object_id) {
            return DataObjectPool::get(User::class, $ac_object_id);
        }

        return null;
    }

    /**
     * Import user.
     *
     * @param  StdClass    $bc_user
     * @param  bool|string $role
     * @return User
     */
    private function importUser($bc_user, $role = false)
    {
        $bc_user_email = $bc_user->email_address;

        $user = Users::findByEmail($bc_user_email, true);

        $full_name = $bc_user->name;
        $full_name_array = explode(' ', $full_name);

        if (empty($user)) {
            $temporary_file = $this->downloadAttachment($bc_user->avatar_url);

            if (is_file($temporary_file)) {
                $avatar_location = AngieApplication::storeFile($temporary_file)[1];
                @unlink($temporary_file);
            } else {
                $avatar_location = '';
            }

            $user_type = isset($bc_user->account_owner) && $bc_user->account_owner ? Owner::class : Member::class;
            if ($role === 'Client' || $role === 'PowerClient') {
                $user_type = Client::class;
            }

            $params = [
                'type' => $user_type,
                'email' => $bc_user_email,
                'first_name' => $bc_user_email == $full_name ? null : $full_name_array[0],
                'last_name' => $bc_user_email == $full_name ? null : array_var($full_name_array, 1),
                'company_id' => 0,
                'created_on' => $bc_user->created_at,
                'password' => AngieApplication::authentication()->generateStrongPassword(32),
                'avatar_location' => $avatar_location,
            ];

            if ($role == 'PowerClient') {
                $params['custom_permissions'] = [
                    User::CAN_MANAGE_TASKS,
                ];
            }

            try {
                $user = Users::create($params, true, false);
            } catch (Exception $e) {
                AngieApplication::log()->error(
                    'Failed to import user {email_address}.',
                    [
                        'email_address' => $bc_user_email,
                        'exception' => $e,
                    ]
                );
            }
        } else {
            if (!$user->getFirstName() && $full_name_array[0]) {
                $user->setFirstName($full_name_array[0]);
            }

            if (!$user->getLastName()) {
                $last_name = !empty($full_name_array[1]) ? $full_name_array[1] : $full_name_array[0];
                $user->setFirstName($last_name);
            }

            if (isset($bc_user->account_owner) && $bc_user->account_owner) {
                $user->setType('Owner');
            }

            try {
                $user->save();
            } catch (Exception $e) {
                AngieApplication::log()->error(
                    'Failed to update user {email_address}.',
                    [
                        'email_address' => $user->getEmail(),
                        'exception' => $e,
                    ]
                );
            }
        }

        // disable morning mail for all members, not for owner(s)
        if (isset($bc_user->account_owner) && !$bc_user->account_owner) {
            ConfigOptions::setValueFor(['notifications_user_send_morning_paper' => false], $user);
        }

        $this->mapUser($bc_user->id, $user->getId());

        return $user;
    }

    /**
     * Download a single attachment file.
     *
     * @param  string $url
     * @return string
     */
    private function downloadAttachment($url)
    {
        $bc_tmp_attachments = WORK_PATH . '/' . AngieApplication::getAccountId() . '-bc_attachments';
        recursive_mkdir($bc_tmp_attachments);

        $ext = get_file_extension($url, true);
        $ext = substr($ext, 0, strpos($ext, '?'));

        $filename = $bc_tmp_attachments . '/bc_attachment_' . make_string() . $ext;

        // if client not initialized, initialize it now
        if ($this->http_client === false) {
            $this->http_client = new GuzzleHttp\Client();
        }

        // request options
        $request_options = [];

        // authorization
        $request_options['auth'] = [$this->username, $this->password];

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

        $request_options['allow_redirects'] = false;

        // output response to file
        $request_options['save_to'] = $filename;

        // execute request
        $response = $this->http_client->get($url, $request_options);

        if ($response->getStatusCode() === 302) {
            unset($request_options['auth']);
            $this->http_client->get($response->getHeaders()['Location'][0], $request_options);
        }

        return $filename;
    }

    /**
     * Map ActiveCollab user ID with Basecamp user.
     *
     * @param int $bc_user_id
     * @param int $user_id
     */
    private function mapUser($bc_user_id, $user_id)
    {
        DB::execute('REPLACE INTO ' . $this->getMappingTableName($this->mapping_table_name) . ' (object_type, tool_object_id, ac_object_id) VALUES (?, ?, ?)', 'User', $bc_user_id, $user_id);
    }

    /**
     * Return user details from basecamp.
     *
     * @param $user_id
     * @return mixed
     */
    private function getUserById($user_id)
    {
        return $this->makeRequest('/people/' . $user_id);
    }

    /**
     * Return people on this project.
     *
     * @param $project_id
     * @return mixed
     */
    private function getProjectPeopleByProjectId($project_id)
    {
        return $this->makeRequest('/projects/' . $project_id . '/accesses');
    }

    /**
     * Add TO-DO LISTS to created project.
     *
     * @param $bc_project_data
     */
    private function addToDoListsToProject(Project $project, $bc_project_data)
    {
        $lists = $bc_project_data->todolists;

        if ($lists->remaining_count > 0) {
            $to_do_lists = $this->getToDoListsByProjectId($bc_project_data->id);
            if (is_foreachable($to_do_lists)) {
                foreach ($to_do_lists as $list) {
                    $this->toDoListToTaskList($project, $list->url);
                }
            }
        }

        if ($lists->completed_count > 0) {
            //completed list
            $completed_to_do_lists = $this->getToDoListsByProjectId($bc_project_data->id, true);
            if (is_foreachable($completed_to_do_lists)) {
                foreach ($completed_to_do_lists as $list) {
                    $this->toDoListToTaskList($project, $list->url);
                }
            }
        }
    }

    /**
     * Return all TO-DO lists by project_id.
     *
     * @param  int   $project_id
     * @param  bool  $completed
     * @return array
     */
    private function getToDoListsByProjectId($project_id, $completed = false)
    {
        if ($completed) {
            return $this->makeRequest('/projects/' . $project_id . '/todolists/completed');
        }

        return $this->makeRequest('/projects/' . $project_id . '/todolists');
    }

    /******************** Common methods ****************************/

    /**
     * Add TO Do list as Task to project.
     *
     * @param string $to_do_list_url
     */
    private function toDoListToTaskList(Project $project, $to_do_list_url)
    {
        $to_do_list = $this->makeRequest($to_do_list_url);

        $creator = $this->getBasecampUser($to_do_list->creator->id);

        /** @var TaskList $task_list */
        $task_list = TaskLists::create(
            [
                'project_id' => $project->getId(),
                'name' => $this->maxLength($to_do_list->name),
                'created_by_id' => $creator->getId(),
                'created_on' => $to_do_list->created_at,
            ],
            true,
            false
        );

        // Create discussion with todos lists comments
        if (is_foreachable($to_do_list->comments)) {
            $discussion = Discussions::create([
                'project_id' => $project->getId(),
                'name' => $this->maxLength(lang('Comments from ":name" ToDo List', ['name' => $to_do_list->name])),
                'body' => trim($to_do_list->description),
                'created_on' => $to_do_list->created_at,
                'created_by_id' => $creator->getId(),
                'notify_subscribers' => $this->send_emails,
            ]);

            $this->addComments($discussion, $to_do_list->comments);
        }

        if (is_foreachable($to_do_list->todos->remaining)) {
            foreach ($to_do_list->todos->remaining as $todo_block) {
                $this->toDoToTask($task_list, $todo_block->url);
            }
        }
        if (is_foreachable($to_do_list->todos->completed)) {
            foreach ($to_do_list->todos->completed as $todo_block) {
                $this->toDoToTask($task_list, $todo_block->url);
            }
        }

        if (!empty($to_do_list->completed)) {
            $completer = !empty($to_do_list->completer) ? $this->getBasecampUser($to_do_list->completer->id) : Users::findFirstOwner();
            $completed_at = !empty($to_do_list->completed_at) ? new DateTimeValue($to_do_list->completed_at) : DateTimeValue::now();

            $this->completeObject($task_list, $completer, $completed_at);
        }
    }

    /**
     * Add coments to object.
     *
     * @param $bc_comments
     */
    private function addComments(IComments $parent, $bc_comments)
    {
        if (is_foreachable($bc_comments)) {
            foreach ($bc_comments as $bc_comment) {
                $user = $this->getBasecampUser($bc_comment->creator->id);
                $additional_params = [];
                $formated_attachments = [];

                if (is_foreachable($bc_comment->attachments)) {
                    foreach ($bc_comment->attachments as $bc_attachment) {
                        if ($this->checkFile($bc_attachment)) {
                            $this->uploadGoogleDriveFiles($bc_attachment, $parent);

                            continue;
                        }

                        $path = $this->downloadAttachment($bc_attachment->url);

                        $formated_attachments[] = [
                            'path' => $path,
                            'filename' => $this->maxLength($bc_attachment->name),
                            'type' => $bc_attachment->content_type,
                        ];
                    }
                }

                $additional_params['created_on'] = $bc_comment->created_at;
                $additional_params['notify_subscribers'] = $this->send_emails;

                $body = strip_tags($bc_comment->content) != '' ? $bc_comment->content : '<i>No comment</i>';

                $comment = $parent->submitComment($body, $user, $additional_params);

                if ($comment instanceof Comment && is_foreachable($formated_attachments)) {
                    $comment->attachFilesFromArray($formated_attachments); //@todo should include "created_on" and "created_by" additional params
                }

                if (is_foreachable($formated_attachments)) {
                    foreach ($formated_attachments as $attachment) {
                        @unlink($attachment['path']); //unlink tmp files
                    }
                }
            }
        }
    }

    /**
     * Skip Google documents and check file size.
     *
     * @param $bc_attachment
     * @return bool
     */
    private function checkFile($bc_attachment)
    {
        if ((isset($bc_attachment->linked_source) && $bc_attachment->linked_source == 'google') ||
            (isset($bc_attachment->byte_size) && $bc_attachment->byte_size == 0)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Add Google files.
     *
     * @param $bc_attachment
     * @param $parent
     * @return DataObject|File
     * @throws InvalidParamError
     */
    public function uploadGoogleDriveFiles($bc_attachment, $parent)
    {
        $attributes = [
            'type' => GoogleDriveUploadedFile::class,
            'name' => $bc_attachment->name,
            'mime_type' => $bc_attachment->content_type,
            'size' => $bc_attachment->byte_size,
            'location' => date('Y-m') . '/' . make_string(40),
        ];
        $file = UploadedFiles::create($attributes, false);

        if ($file instanceof GoogleDriveUploadedFile && isset($bc_attachment->link_url)) {
            $file->setUrl($bc_attachment->link_url);
        }

        $file->save();
        if ($parent instanceof Project) {
            $file_name = $bc_attachment->name ? $this->maxLength($bc_attachment->name) : 'file_name';
            $body = !empty($bc_attachment->content) ? $bc_attachment->content : '';

            $external_file = Files::create([
                'project_id' => $parent->getId(),
                'name' => $file_name,
                'body' => $body,
                'uploaded_file_code' => $file->getCode(),
                'is_hidden_from_clients' => false,
            ]);
        } else {
            $external_file = $parent->attachUploadedFile($file);
        }

        return $external_file;
    }

    /**
     * Add ToDos by Url to specific object.
     *
     * @param string $todo_url
     */
    private function toDoToTask(TaskList $parent, $todo_url)
    {
        $todo = $this->makeRequest($todo_url);

        $creator = $this->getBasecampUser($todo->creator->id);
        $assignee = !empty($todo->assignee) ? $this->getBasecampUser($todo->assignee->id) : null;

        // check that assignee is member of a project
        if ($assignee && !$parent->getProject()->isMember($assignee)) {
            $assignee = null;
        }

        $trimmed_task_name = $this->maxLength($todo->content);

        if ($todo->private && ($assignee && $assignee->isClient())) {
            $assignee = null;
        }

        $task = Tasks::create(
            [
                'name' => $trimmed_task_name,
                'body' => $trimmed_task_name != $todo->content ? $todo->content : '',
                'task_list_id' => $parent->getId(),
                'project_id' => $parent->getProjectId(),
                'created_on' => $todo->created_at,
                'created_by' => $creator->getId(),
                'due_on' => $todo->due_at ?? null,
                'assignee_id' => $assignee ? $assignee->getId() : 0,
                'notify_subscribers' => $this->send_emails,
                'is_hidden_from_clients' => $todo->private == false ? 0 : 1,
            ],
            false,
            false
        );

        $this->saveTask($task);

        $this->addComments($task, $todo->comments);
        $this->addAttachments($task, $todo->attachments);
        $this->addSubsribers($task, $todo->subscribers);

        if (!empty($todo->completed)) {
            $completer = !empty($todo->completer) ? $this->getBasecampUser($todo->completer->id) : Users::findFirstOwner();
            $completed_at = !empty($todo->completed_at) ? new DateTimeValue($todo->completed_at) : DateTimeValue::now();

            $this->completeObject($task, $completer, $completed_at);
        }
    }

    /**
     * Add attachments from basecamp to project object.
     *
     * @param $bc_attachments_block
     */
    private function addAttachments(IAttachments $parent, $bc_attachments_block)
    {
        if (is_foreachable($bc_attachments_block)) {
            $formated_attachments = [];

            foreach ($bc_attachments_block as $bc_attachment) {
                if ($this->checkFile($bc_attachment, $parent)) {
                    $this->uploadGoogleDriveFiles($bc_attachment, $parent);

                    continue;
                }

                $path = $this->downloadAttachment($bc_attachment->url);

                $formated_attachments[] = [
                    'path' => $path,
                    'filename' => $this->maxLength($bc_attachment->name),
                    'type' => $bc_attachment->content_type,
                ];
            }

            $parent->attachFilesFromArray($formated_attachments); //@todo should include "created_on" and "created_by" additional params

            if (is_foreachable($formated_attachments)) {
                foreach ($formated_attachments as $attachment) {
                    @unlink($attachment['path']); // Unlink tmp files
                }
            }
        }
    }

    /**
     * Subscribe users from "subscribers" bc block to parent.
     *
     * @param $bc_subscribers_block
     */
    private function addSubsribers(ISubscriptions $parent, $bc_subscribers_block)
    {
        if (is_foreachable($bc_subscribers_block) && $parent instanceof ISubscriptions) {
            $subscribers = [];

            foreach ($bc_subscribers_block as $bc_subscriber) {
                $subscribers[] = $this->getBasecampUser($bc_subscriber->id);
            }
            $parent->setSubscribers($subscribers);
        }
    }

    /************** Mappings ********************/

    /**
     * Add discussions/messages to project.
     *
     * @param $bc_project
     */
    private function addDiscussionsToProjects(Project $project, $bc_project, callable $output = null)
    {
        $discussions = $this->getDiscussionsByProjectId($bc_project->id);
        if (is_foreachable($discussions)) {
            foreach ($discussions as $discussion_block) {
                $discussion_data = $this->getDiscussionByUrl($discussion_block->topicable->url);
                $creator = $this->getBasecampUser($discussion_data->creator->id);

                /** @var Discussion $discussion */
                $discussion = Discussions::create([
                    'project_id' => $project->getId(),
                    'name' => $this->maxLength($discussion_data->subject),
                    'body' => $discussion_data->content ? $discussion_data->content : '',
                    'created_by_id' => $creator->getId(),
                    'created_on' => $discussion_data->created_at,
                    'notify_subscribers' => $this->send_emails,
                    'is_hidden_from_clients' => $discussion_data->private == false ? 0 : 1,
                ]);

                $this->outputBuffer($output, "Importing comments for discussion '{$discussion->getName()}'...");
                $this->addComments($discussion, $discussion_data->comments);
                $this->outputBuffer($output, "Discussion's comments are imported.\r\n");

                $this->outputBuffer($output, "Importing attachments for discussion '{$discussion->getName()}'...");
                $this->addAttachments($discussion, $discussion_data->attachments);
                $this->outputBuffer($output, "Discussion's attachments are imported.\r\n");

                $this->addSubsribers($discussion, $discussion_data->subscribers);
            }
        }
    }

    /**
     * Return all topics that have "topicable" type "Message" - discussions.
     *
     * @param  int   $project_id
     * @return array
     */
    private function getDiscussionsByProjectId($project_id)
    {
        $topics = $this->getTopicsGroupedByType($project_id);

        return !empty($topics['Message']) && is_array($topics['Message']) ? $topics['Message'] : [];
    }

    /**
     * Return topics grouped by type.
     *
     * @param  int   $project_id
     * @return array
     */
    private function getTopicsGroupedByType($project_id)
    {
        $grouped_topics = [];
        $page = 1;
        do {
            $topics = $this->getTopicsByProjectId($project_id, $page);
            foreach ($topics as $topic) {
                $grouped_topics[$topic->topicable->type][] = $topic;
            }
            ++$page;
        } while ($this->getTopicsByProjectId($project_id, $page));

        return $grouped_topics;
    }

    /****************** Attachments ***************/

    /**
     * Return all topics.
     *
     * @param $project_id
     * @param $page
     * @return mixed
     */
    private function getTopicsByProjectId($project_id, $page = 1)
    {
        $params = [
            'page' => $page,
        ];

        return $this->makeRequest('/projects/' . $project_id . '/topics', $params);
    }

    /**
     * Return full discussion/message data from basecamp by Url.
     *
     * @param $url
     * @return mixed
     */
    private function getDiscussionByUrl($url)
    {
        return $this->makeRequest($url);
    }

    /**
     * Add files/uploads to project.
     *
     * @param $bc_project
     */
    private function addFilesToProjects(Project $project, $bc_project)
    {
        $uploads = $this->getUploadsByProjectId($bc_project->id);
        if (is_foreachable($uploads)) {
            foreach ($uploads as $upload_block) {
                //get full message data
                $upload_data = $this->getUploadByUrl($upload_block->attachable->url);

                if (is_foreachable($upload_data->attachments)) {
                    $attachment_block = $upload_data->attachments[0];

                    $file_name = $upload_block->name ? $this->maxLength($upload_block->name) : 'file_name';
                    $body = !empty($upload_block->content) ? $upload_block->content : '';
                    $creator = $this->getBasecampUser($upload_block->creator->id);

                    $is_external_file = $this->checkFile($upload_block);
                    if ($is_external_file) {
                        $this->uploadGoogleDriveFiles($upload_block, $project);
                    } else {
                        $path = $this->downloadAttachment($upload_block->url);
                        $uploaded_file = UploadedFiles::addFile($path, $upload_block->name, $upload_block->content_type, false);

                        /* @var File $new_file */
                        $file = Files::create([
                            'type' => LocalFile::class,
                            'project_id' => $project->getId(),
                            'name' => $file_name,
                            'body' => $body,
                            'created_on' => $upload_block->created_at,
                            'created_by_id' => $creator->getId(),
                            'uploaded_file_code' => $uploaded_file->getCode(),
                            'is_hidden_from_clients' => empty($uploaded_file->private) ? 0 : 1,
                        ]);

                        /** @var WarehouseIntegration $warehouse_integration */
                        $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);

                        if ($warehouse_integration->isInUse()) {
                            $job = new UploadLocalFileToWarehouse([
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

                    // Create discussion with file comments
                    if (is_foreachable($upload_data->comments)) {
                        $discussion = Discussions::create([
                            'project_id' => $project->getId(),
                            'name' => $this->maxLength('Comments from "' . $file_name . '" File'),
                            'body' => trim($body),
                            'created_on' => $attachment_block->created_at,
                            'created_by_id' => $creator->getId(),
                            'notify_subscribers' => $this->send_emails,
                        ]);

                        $this->addComments($discussion, $upload_data->comments);
                    }
                }
            }
        }
    }

    /**
     * Return all uploads that have "attachable" type "Upload".
     *
     * @param  int   $project_id
     * @return array
     */
    private function getUploadsByProjectId($project_id)
    {
        $topics = $this->getAttachmentsGroupedByType($project_id);

        return !empty($topics['Upload']) ? $topics['Upload'] : [];
    }

    /**
     * Return attachments grouped by type.
     *
     * @param $project_id
     * @return array
     */
    private function getAttachmentsGroupedByType($project_id)
    {
        $grouped_attachments = [];
        $page = 1;
        do {
            $attachments = $this->getAttachmentsByProjectId($project_id, $page);
            foreach ($attachments as $attachment) {
                $grouped_attachments[$attachment->attachable->type][] = $attachment;
            }
            ++$page;
        } while ($this->getAttachmentsByProjectId($project_id, $page));

        return $grouped_attachments;
    }

    /**
     * Return all attachments.
     *
     * @param $project_id
     * @param $page
     * @return mixed
     */
    private function getAttachmentsByProjectId($project_id, $page = 1)
    {
        $params = [
            'page' => $page,
        ];

        return $this->makeRequest('/projects/' . $project_id . '/attachments', $params);
    }

    /****************** Topics ***************/

    /**
     * Return full upload data from basecamp by Url.
     *
     * @param $url
     * @return mixed
     */
    private function getUploadByUrl($url)
    {
        return $this->makeRequest($url);
    }

    /**
     * Add text documents to project.
     *
     * @param StdClass $bc_project
     */
    private function addTextDocumentsToProjects(Project $project, $bc_project)
    {
        $text_documents = $this->getTextDocumentsByProjectId($bc_project->id);

        if (is_foreachable($text_documents)) {
            foreach ($text_documents as $document_block) {
                $text_document_data = $this->getTextDocumentByUrl($document_block->url);

                $creator = $this->getBasecampUser($text_document_data->last_updater->id);

                /** @var Note $note */
                $note = Notes::create([
                    'project_id' => $project->getId(),
                    'name' => $this->maxLength($text_document_data->title),
                    'body' => $text_document_data->content ? $text_document_data->content : '',
                    'created_on' => $text_document_data->created_at,
                    'created_by_id' => $creator->getId(),
                    'notify_subscribers' => $this->send_emails,
                    'is_hidden_from_clients' => $text_document_data->private == false ? 0 : 1,
                ]);

                $this->addComments($note, $text_document_data->comments);
                $this->addSubsribers($note, $text_document_data->subscribers);
            }
        }
    }

    /**
     * Return all text documents by project id.
     *
     * @param $project_id
     * @return mixed
     */
    private function getTextDocumentsByProjectId($project_id)
    {
        return $this->makeRequest('/projects/' . $project_id . '/documents');
    }

    /**
     * Return full text document data from basecamp by Url.
     *
     * @param $url
     * @return mixed
     */
    private function getTextDocumentByUrl($url)
    {
        return $this->makeRequest($url);
    }

    /**
     * Map ActiveCollab project ID with Basecamp project.
     *
     * @param int $bc_project_id
     * @param int $project_id
     */
    private function mapProject($bc_project_id, $project_id)
    {
        DB::execute('REPLACE INTO ' . $this->getMappingTableName($this->mapping_table_name) . ' (object_type, tool_object_id, ac_object_id) VALUES (?, ?, ?)', 'Project', $bc_project_id, $project_id);
    }

    public function canView(User $user): bool
    {
        return $user instanceof Owner;
    }

    /**
     * Send users invite.
     *
     * @return BasecampImporterIntegration
     */
    public function invite()
    {
        return $this->inviteUsers($this->mapping_table_name);
    }
}
