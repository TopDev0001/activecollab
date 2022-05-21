<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\AsanaAttachmentsImport;
use ActiveCollab\JobsQueue\Jobs\JobInterface;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use Asana\Client;
use Asana\Errors\AsanaError;
use Emojione\Client as EmojiClient;
use Emojione\Ruleset;
use Michelf\Markdown;

/**
 * Asana integration.
 */
class AsanaImporterIntegration extends AbstractImporterIntegration
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    private $mapping_table_name = 'asana_migration_mappings';

    /**
     * Http Client.
     *
     * @var GuzzleHttp\Client
     */
    private $http_client = false;

    /**
     * Return integration name.
     *
     * @return string
     */
    public function getName()
    {
        return 'Asana importer';
    }

    public function getShortName(): string
    {
        return 'asana-importer';
    }

    protected function getLogEventPrefix(): string
    {
        return 'asana';
    }

    /**
     * Get access token.
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->getAdditionalProperty('access_token');
    }

    /**
     * Set access token.
     *
     * @param $access_token
     * @return string
     */
    public function setAccessToken($access_token)
    {
        $this->setAdditionalProperty('access_token', $access_token);

        return $this->getAccessToken();
    }

    /**
     * @param $active_workspaces
     * @return mixed
     */
    public function setActiveWorkspaces($active_workspaces)
    {
        return $this->setAdditionalProperty('active_workspaces', $active_workspaces);
    }

    /**
     * @return mixed
     */
    public function getActiveWorkspaces()
    {
        return $this->getAdditionalProperty('active_workspaces');
    }

    /**
     * @return mixed
     */
    public function getSelectedWorkspaces()
    {
        return $this->getAdditionalProperty('selected_workspaces', []);
    }

    /**
     * @return mixed
     */
    public function setSelectedWorkspaces($selected_workspace)
    {
        return $this->setAdditionalProperty('selected_workspaces', $selected_workspace);
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function validateCredentials()
    {
        try {
            $this->client = Client::accessToken(
                $this->getAccessToken(),
                [
                    'headers' => ['asana-disable' => 'new_sections'],
                ]
            );
            $this->client->options['max_retries'] = 10;
            $me = $this->client->users->me();
            $workspaces = $me->workspaces;

            $listWorkspaces = [];
            if (is_foreachable($workspaces)) {
                foreach ($workspaces as $workspace) {
                    $tmp['name'] = trim($workspace->name);
                    $tmp['id'] = $workspace->gid;
                    $tmp['selected'] = false;

                    $listWorkspaces[] = $tmp;
                }
            }

            $this->setActiveWorkspaces($listWorkspaces);
            $this->save();

            return $this;
        } catch (Exception $e) {
            $this->setAccessToken(null);
            $this->save();

            throw new Exception('Authentication Failed. Make sure you enter the correct access token.');
        }
    }

    /**
     * @return bool
     */
    public function hasValidAccess()
    {
        return $this->getAccessToken() !== null;
    }

    /**
     * Authorize with Asana.
     *
     * @return $this
     */
    public function authorize(array $params)
    {
        $token = array_var($params, 'access_token');
        $this->setAccessToken($token);
        $this->save();

        return $this;
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
            'workspaces' => $this->getActiveWorkspaces(),
        ]);
    }

    /**
     * Schedule import process.
     *
     * @return AsanaImporterIntegration
     */
    public function &scheduleImport()
    {
        $command_arguments = [
            $this->getAccessToken(),
        ];

        $this->dispatchJob(
            [
                'command' => 'import_asana_account',
                'command_arguments' => $command_arguments,
                'log_output_to_file' => AngieApplication::getAvailableWorkFileName('asana-import-' . date('Y-m-d H-i-s'), 'txt', null),
            ]
        );

        $this->setStatus(self::STATUS_PENDING);
        $this->save();

        return $this;
    }

    public function startImport(callable $output = null)
    {
        try {
            parent::startImport($output);

            $this->migrateMappingTable($this->mapping_table_name);

            return $this->importWorkspaces($output);
        } catch (ValidationErrors $e) {
            AngieApplication::log()->error('Asana importer validation error', [
                'error_message' => $e->getErrors(),
                'trace' => $e,
            ]);

            $this->setStatus(self::STATUS_FAILED);
            $this->save();

            throw $e;
        } catch (\Exception $e) {
            AngieApplication::log()->error('Asana importer error', [
                'message' => $e->getMessage(),
            ]);

            $this->setStatus(self::STATUS_FAILED);
            $this->save();

            throw $e;
        }
    }

    /**
     * Import all workspaces.
     *
     * @param  callable|null $output
     * @throws Exception
     */
    private function importWorkspaces(callable $output)
    {
        $this->setStatus(self::STATUS_IMPORTING);
        $this->save();

        $workspaces = $this->getSelectedWorkspaces();

        if (!count($workspaces)) {
            $this->setStatus(self::STATUS_FAILED);
            $this->save();

            throw new Exception('There are no workspaces in this Asana account');
        }

        $this->setImportTotal($workspaces);
        $this->setImportProgress(0);
        $this->save();

        $this->outputBuffer($output, [
            "****************************************************************\r\n",
            '***** There is ' . count($workspaces) . " active workspace(s) to import. Importing ... **\r\n",
            "****************************************************************\r\n",
        ]);

        $i = 1;
        foreach ($workspaces as $workspace) {
            $this->setImportProgress($i);
            $detailed_workspace = $this->client->workspaces->findById($workspace);
            $this->setImportLabel($detailed_workspace->name);
            $this->save();

            if (!$this->getMappedObjectType($this->mapping_table_name, $detailed_workspace->gid, 'Workspace')) {
                $this->outputBuffer($output, [
                    "----------------------------------------- ONE WORKSPACE\r\n",
                    "Importing workspace: '" . $detailed_workspace->name . "'\r\n",
                ]);
                $this->outputBuffer($output, 'Importing users...');
                $this->importUsersFromWorkspace($detailed_workspace->gid);
                $this->outputBuffer($output, "Users are imported.\r\n");

                //in asana only organisations have teams
                if ($detailed_workspace->is_organization) {
                    $this->outputBuffer($output, 'Importing teams...');
                    $this->importTeams($detailed_workspace->gid);
                    $this->outputBuffer($output, "Teams are imported.\r\n");
                    $this->importCustomFieldEnum($detailed_workspace->gid);
                }

                $this->importWorkspaceLabels($detailed_workspace->gid);
                $this->importWorkspace($detailed_workspace->gid, $output);
                $this->getTasksWitoutProject($detailed_workspace->gid);

                $this->outputBuffer($output, [
                    "Workspace '" . $detailed_workspace->name . "' is imported.\r\n",
                    "----------------------------------------- END OF ONE WORKSPACE\r\n",
                ]);
            } else {
                if ($output) {
                    $this->outputBuffer($output, "Workspace '" . $detailed_workspace->name . "' already imported. Skipped. ...\r\n");
                }
            }

            ++$i;
        }

        $this->setStatus(self::STATUS_INVITING);
        $this->save();
    }

    /**
     * @param $workspace_id
     */
    private function importUsersFromWorkspace($workspace_id)
    {
        $users = $this->client->users->findByWorkspace($workspace_id);
        foreach ($users as $user) {
            $detailed_user = $this->client->users->findById($user->gid);
            if (!empty($detailed_user->email)) {
                $ac_user = Users::findByEmail($detailed_user->email, true);

                if (empty($ac_user)) {
                    if (!empty($detailed_user->photo)) {
                        $avatar_code = '';
                        $temporary_file = $this->downloadAttachment($detailed_user->photo->image_128x128);

                        if (is_file($temporary_file)) {
                            [$target_path, $location] = AngieApplication::storeFile($temporary_file);

                            $file_arr = explode('/', $temporary_file);
                            $target_arr = explode('/', $target_path);

                            $avatar_code = $target_arr[count($target_arr) - 1];

                            UploadedFiles::create([
                                'name' => $this->maxLength($file_arr[count($file_arr) - 1]),
                                'mime_type' => 'image/png',
                                'size' => filesize($temporary_file),
                                'location' => $location,
                                'md5' => md5_file($target_path),
                                'code' => $avatar_code,
                            ]);

                            @unlink($temporary_file);
                        }
                    }

                    $first_last_name = explode(' ', $detailed_user->name);
                    $params = [
                        'type' => Member::class,
                        'email' => $detailed_user->email,
                        'first_name' => isset($first_last_name[0]) ? $first_last_name[0] : null,
                        'last_name' => isset($first_last_name[1]) ? $first_last_name[1] : null,
                        'company_id' => 0,
                        'password' => AngieApplication::authentication()->generateStrongPassword(32),
                        'uploaded_avatar_code' => !empty($avatar_code) ? $avatar_code : '',
                    ];

                    $ac_user = Users::create($params, true);
                }

                $this->mapObject($this->mapping_table_name, $user->gid, $ac_user->getId(), 'User');
            }
        }
    }

    /**
     * @param $organisation_id
     */
    private function importTeams($organisation_id)
    {
        $teams = $this->client->teams->findByOrganization($organisation_id);
        $team_users_batch = new DBBatchInsert('team_users', ['team_id', 'user_id'], 500, DBBatchInsert::REPLACE_RECORDS);
        foreach ($teams as $team) {
            $ac_team = $this->getMappedObjectType($this->mapping_table_name, $team->gid, 'Team');
            if (empty($ac_team)) {
                $team_members = $this->client->teams->users($team->gid);

                if (iterator_count($team_members)) {
                    $team_name = trim($team->name);
                    $team_exists = (bool) Teams::count(['name = ?', $team_name]);

                    if ($team_exists) {
                        $team_name = $team_name . ' Asana team';
                    }

                    /** @var Team $created_team */
                    $created_team = Teams::create([
                        'name' => $this->maxLength($team_name, 100),
                    ]);

                    foreach ($team_members as $team_member) {
                        $team_users_batch->insert($created_team->getId(), $this->getUser($team_member->gid)->getId());
                    }

                    $this->mapObject($this->mapping_table_name, $team->gid, $created_team->getId(), 'Team');
                }
            }
        }
        $team_users_batch->done();
    }

    /**
     * Create project and tasks for tasks whiteout projects
     * Asana API requires assignee and workspace.
     *
     * @param $workspace_id
     * @throws InvalidParamError
     */
    private function getTasksWitoutProject($workspace_id)
    {
        //get all users from workspace
        $users = $this->client->users->findByWorkspace($workspace_id);

        $tasks_for_import = [];
        foreach ($users as $user) {
            //get all tasks from user on workspace, because API requires workspace + assignee
            $tasks = $this->client->tasks->findAll(['assignee' => $user->gid, 'workspace' => $workspace_id]);

            foreach ($tasks as $task) {
                $tmp_task = $this->client->tasks->findById($task->gid);
                // filtering tasks whiteout projects and excluding subtasks
                if ((count($tmp_task->projects) === 0) && is_null($tmp_task->parent)) $tasks_for_import[] = $tmp_task;
            }
        }

        if (count($tasks_for_import) > 0) {
            $workspace = $this->client->workspaces->findById($workspace_id);

            $params = [
                'name' => $this->maxLength(trim($workspace->name)),
                'company_id' => AngieApplication::getContainer()
                    ->get(OwnerCompanyResolverInterface::class)
                        ->getId(),
                'skip_default_task_list' => true,
                'send_invitations' => $this->send_emails,
            ];

            $created_project = Projects::create($params);

            $sections = [];
            //first create tasks lists
            foreach ($tasks_for_import as $task) {
                $section_id = null;
                //check and create tasks list if tasks are tasks list
                if (!empty($task->memberships) && !empty($task->memberships[0]->section)) {
                    $section_id = $task->memberships[0]->section->gid;
                }

                if ($section_id === $task->gid) {
                    $section_detals = $this->getSectionsById($task->gid);
                    $task_list = TaskLists::create([
                        'project_id' => $created_project->getId(),
                        'name' => $this->maxLength(trim($section_detals->name)),
                        'created_on' => new DateTimeValue($section_detals->created_at),
                    ]);

                    $sections[$task->gid] = $task_list->getId();
                }
            }

            foreach ($tasks_for_import as $task) {
                $section_id = null;
                if (!empty($task->memberships) && !empty($task->memberships[0]->section)) {
                    $section_id = $task->memberships[0]->section->gid;
                }

                if ($section_id !== $task->gid) {
                    $this->importTask($task->gid, $created_project, $sections);
                }
            }

            $this->mapObject($this->mapping_table_name, $workspace->gid, $created_project->getId(), 'Project');
        }
    }

    /**
     * @param $workspace_id
     * @return int
     * @throws DBQueryError
     * @throws InvalidParamError
     * @throws ValidationErrors
     */
    private function importWorkspaceLabels($workspace_id)
    {
        $tags = $this->client->tags->findByWorkspace($workspace_id);

        if (empty($tags)) {
            return 0;
        }

        $i = 1;
        foreach ($tags as $tag) {
            $detailed_tag = $this->client->tags->findById($tag->gid);
            $name = $detailed_tag->name != '' ? strtoupper($detailed_tag->name) : strtoupper($detailed_tag->color);
            $color = $this->getColor($detailed_tag->color);
            $name = !empty(trim($name)) ? trim($name) : 'empty';
            if ($id = DB::executeFirstCell('SELECT id FROM labels WHERE type = ? AND name = ? AND color = ?', 'TaskLabel', $name, $color)) {
                // set existing label to be global
                $ac_label = Labels::findById($id);
                $ac_label->setFieldValue('is_global', true);
                $ac_label->save();
            } else {
                // create not global label
                if ($id = DB::executeFirstCell('SELECT id FROM labels WHERE type = ? AND name = ?', 'TaskLabel', $name)) {
                    $name .= ' ' . $i;
                    ++$i;
                }
                $ac_label = Labels::create([
                    'type' => 'TaskLabel',
                    'name' => $name,
                    'color' => $color,
                    'is_global' => false,
                ]);

                $this->mapObject($this->mapping_table_name, $detailed_tag->gid, $ac_label->getId(), 'Label');
            }
        }
    }

    /**
     * @param $color
     * @return string|null
     */
    private function getColorForEnums($color)
    {
        $ac_color = null;
        switch ($color) {
            case 'red':
                $ac_color = '#E8384F';

                break;
            case 'orange':
                $ac_color = '#FD612C';

                break;
            case 'yellow-orange':
                $ac_color = '#FD9A00';

                break;
            case 'yellow':
                $ac_color = '#EEC300';

                break;
            case 'yellow-green':
                $ac_color = '#A4CF30';

                break;
            case 'green':
                $ac_color = '#62D26F';

                break;
            case 'blue-green':
                $ac_color = '#37C5AB';

                break;
            case 'aqua':
                $ac_color = '#20AAEA';

                break;
            case 'blue':
                $ac_color = '#4186E0';

                break;
            case 'indigo':
                $ac_color = '#7A6FF0';

                break;
            case 'purple':
                $ac_color = '#AA62E3';

                break;
            case 'magenta':
                $ac_color = '#E362E3';

                break;
            case 'hot-pink':
                $ac_color = '#EA4E9D';

                break;
            case 'pink':
                $ac_color = '#FC91AD';

                break;
            case 'cool-gray':
                $ac_color = '#8DA3A6';

                break;

            default: $ac_color = '#F8F8F9';
        }

        return $ac_color;
    }

    /**
     * @param $color
     * @return string|null
     */
    private function getColor($color)
    {
        $ac_color = null;
        switch ($color) {
            case 'dark-pink':
                $ac_color = '#FBD6E7';

                break;
            case 'dark-green':
                $ac_color = '#98B57C';

                break;
            case 'dark-blue':
                $ac_color = '#BEACF9';

                break;
            case 'dark-red':
                $ac_color = '#FF9C9C';

                break;
            case 'dark-teal':
                $ac_color = '#B9E4E0';

                break;
            case 'dark-brown':
                $ac_color = '#EAC2AD';

                break;
            case 'dark-orange':
                $ac_color = '#FBBB75';

                break;
            case 'dark-purple':
                $ac_color = '#C49CB6';

                break;
            case 'dark-warm-gray':
                $ac_color = '#DDDDDD';

                break;
            case 'light-pink':
                $ac_color = '#FBD6E7';

                break;
            case 'light-green':
                $ac_color = '#C3E799';

                break;
            case 'light-blue':
                $ac_color = '#BEEAFF';

                break;
            case 'light-red':
                $ac_color = '#FBD6E7';

                break;
            case 'light-teal':
                $ac_color = '#EAC2AD';

                break;
            case 'light-yellow':
                $ac_color = '#FDF196';

                break;
            case 'light-orange':
                $ac_color = '#EAC2AD';

                break;
            case 'light-purple':
                $ac_color = '#FBD6E7';

                break;
            case 'light-warm-gray':
                $ac_color = '#DDDDDD';

                break;

            default: $ac_color = '#888';
        }

        return $ac_color;
    }

    /**
     * @param      $workspace_id
     * @return int
     */
    private function importWorkspace($workspace_id, callable $output)
    {
        $projects = $this->client->projects->findAll(['workspace' => $workspace_id]);

        if (empty($projects)) {
            return 0;
        }

        $asana_projects = [];

        foreach ($projects as $project) {
            $asana_projects[] = $project;
        }

        foreach ($asana_projects as $project) {
            if (!$this->getMappedObjectType($this->mapping_table_name, $project->gid, 'Project')) {
                $this->outputBuffer($output, [
                    "----------------------------------------- ONE PROJECT\r\n",
                    "Importing project: '" . $project->name . "'\r\n",
                ]);

                $this->importProject($project->gid);

                $this->outputBuffer($output, [
                    "Project '" . $project->name . "' is imported.\r\n",
                    "----------------------------------------- END OF ONE PROJECT\r\n",
                ]);
            }else {
                if ($output) {
                    $this->outputBuffer($output, "Project '" . $project->name . "' already imported. Skipped. ...\r\n");
                }
            }
        }
    }

    /**
     * @param $description
     * @param $ac_project
     * @return mixed
     * @throws InvalidParamError
     */
    private function createNoteForProjectDescription($description, $ac_project)
    {
        if (strlen_utf($description) > 191) {
            /** @var Notes $note */
            $note = Notes::create([
                'project_id' => $ac_project->getId(),
                'name' => $this->maxLength('Project description'),
                'body' => $description,
                'created_on' => $ac_project->getCreatedOn(),
                'created_by_id' => $ac_project->getCreatedById(),
            ]);

            return $note->getViewUrl();
        }

        return $description;
    }

    /**
     * Import project.
     *
     * @param $project_id
     */
    private function importProject($project_id)
    {
        $tasks = $this->client->tasks->findAll(['project' => $project_id]);
        $project = $this->client->projects->findById($project_id);

        $members = $project->members;
        $owner = null;
        $first_and_last_name = null;
        $email = null;
        if (!empty($project->owner)) {
            $owner = $this->getUser($project->owner->gid);
            $first_and_last_name = $owner->getName() . ' ' . $owner->getLastName();
            $email = $owner->getEmail();
            $owner = $owner->getId();
        }

        $params = [
            'name' => $this->maxLength(trim($project->name)),
            'company_id' => AngieApplication::getContainer()
                ->get(OwnerCompanyResolverInterface::class)
                    ->getId(),
            'created_on' => new DateTimeValue($project->created_at),
            'created_by_id' => $owner,
            'created_by_name' => $first_and_last_name,
            'created_by_email' => $email,
            'skip_default_task_list' => true,
            'send_invitations' => $this->send_emails,
            'leader_id' => $owner,
        ];

        if ($project->archived) {
            $params = array_merge(
                $params,
                [
                    'completed_on' => $project->modified_at ? new DateTimeValue($project->modified_at) : null,
                    'completed_by_id' => $owner,
                    'completed_by_name' => $first_and_last_name,
                    'completed_by_email' => $email,
                ]
            );
        }

        /** @var Project $created_project */
        $created_project = Projects::create($params);

        $created_project->setBody($this->createNoteForProjectDescription($project->notes, $created_project));

        //Add members to project
        foreach ($members as $member) {
            $user = $this->getUser($member->gid);
            $created_project->addMembers([$user], ['send_invitations' => $this->send_emails]);
        }

        $this->importProjectStatus($created_project->getId(), $project->current_status);
        $sections = [];

        //if project is completed pass time of complation to complete tasks inside of project
        $completed = $project->archived ? new DateTimeValue($project->modified_at) : null;

        $project_sections = $this->client->sections->findByProject($project_id);
        foreach ($project_sections as $project_section) {
            $this->importTaskList($project_section->gid, $created_project->getId(), $sections, $completed);
        }

        // Get all project task ids
        $task_ids = [];
        foreach ($tasks as $task) {
            $task_ids[] = $task->gid;
        }

        // Import project tasks
        foreach ($task_ids as $task_id) {
            $this->importTask($task_id, $created_project, $sections, null, $completed);
        }

        unset($task_ids);

        $this->mapObject($this->mapping_table_name, $project->gid, $created_project->getId(), 'Project');
    }

    /**
     * @param $ac_project_id
     * @param $current_status
     */
    private function importProjectStatus($ac_project_id, $current_status)
    {
        if (!is_null($current_status)) {
            $created_at = new DateTimeValue($current_status->modified_at);
            $user_id = null;
            if (!empty($current_status->author->gid)) {
                $user = $this->getUser($current_status->author->gid);
                $user_id = $user->getId();
            }

            Notes::create([
                'project_id' => $ac_project_id,
                'name' => $this->maxLength('Project status (' . $created_at->formatForUser() . '): ' . $this->colorMeaning($current_status->color)),
                'body' => $current_status->text,
                'created_on' => $created_at,
                'created_by_id' => $user_id,
                'notify_subscribers' => $this->send_emails,
            ]);
        }
    }

    /**
     * @param $color
     * @return string
     */
    private function colorMeaning($color)
    {
        switch ($color) {
            case 'red': return 'This project is not on track and needs attention.';
                break;
            case 'yellow': return '"This project is progressing but there are some risks worth addressing."';
                break;
            case 'green': return 'This project is on track.';
                break;
            default: return '';
        }
    }

    /**
     * @param $section_id
     * @return mixed
     */
    private function getSectionsById($section_id)
    {
        $path = sprintf('/sections/%s', $section_id);

        return $this->client->get($path, [], []);
    }

    /**
     * @param      $section_id
     * @param      $ac_project_id
     * @param null $complited_at
     */
    private function importTaskList($section_id, $ac_project_id, & $sections_list, $complited_at = null)
    {
        $section_detals = $this->getSectionsById($section_id);

        $params = [
            'project_id' => $ac_project_id,
            'name' => $this->maxLength(trim($section_detals->name)),
            'created_on' => new DateTimeValue($section_detals->created_at),
        ];

        if (!is_null($complited_at)) {
            $params = array_merge(
                $params,
                [
                    'completed_on' => $complited_at,
                ]
            );
        }

        /** @var TaskList $task_list */
        $task_list = TaskLists::create($params);

        $sections_list[$section_id] = $task_list->getId();
    }

    /**
     * @param $task_id
     * @return mixed
     */
    private function getCommentsFromTask($task_id)
    {
        $path = sprintf('/tasks/%s/stories', $task_id);

        return $this->client->get($path, [], []);
    }

    /**
     * Check if is needed to create task.
     * @param $task
     * @return bool
     */
    private function checkIfCanBeSubtask($task)
    {
        //check if task have attachments
        $attachments = $this->getAttachments($task->gid);
        if (count($attachments) > 0) return false;

        // check if task have comments, all tasks in Asana have 'system' comments
        $comments = $this->getCommentsFromTask($task->gid);
        $comment_number = 0;
        foreach ($comments as $comment) {
            if ($comment->type === 'comment') $comment_number++;
        }

        if ($comment_number > 0) return false;

        // check if have tags
        $tags = $this->client->tasks->tags($task->gid);
        if (iterator_count($tags) > 0) {
            return false;
        }

        $section_id = null;
        if (!empty($task->memberships) && !empty($section_id = $task->memberships[0]->section)) {
            $section_id = $task->memberships[0]->section->gid;
        }

        if (!is_null($section_id)) {
            if ($task->gid === $section_id) {
                $subtasks = $this->client->tasks->subtasks($task->gid);
                if (iterator_count($subtasks) > 0) return false;
            }
        } else {
            $subtasks = $this->client->tasks->subtasks($task->gid);
            if (iterator_count($subtasks) > 0) return false;
        }

        if (!empty($task->notes)) return false;

        if (!empty($task->custom_fields) && !empty($this->getCustomFields($task->custom_fields))) {
            return false;
        }

        if (count($task->followers) > 1) return false;
        if (!is_null($task->due_on)) return false;

        return true;
    }

    /**
     * @param                   $task_id
     * @param  null             $parent_section
     * @param  null             $complited_at
     * @return object|Task|null
     */
    private function importTask($task_id, Project $project, & $sections, $parent_section = null, $complited_at = null)
    {
        $task = $this->client->tasks->findById($task_id);

        $section_id = null;
        if (!empty($task->memberships) && !empty($task->memberships[0]->section)) {
            $section_id = $task->memberships[0]->section->gid;
        }

        // if is section create all sections
        if (($section_id === $task_id) && (!is_null($section_id))) {
            $this->importTaskList($section_id, $project->getId(), $sections, $complited_at);
        }

        //if is not section
        if (($section_id !== $task_id) || (!$this->checkIfCanBeSubtask($task))) {
            $project_memberships = $task->projects;

            $body = '';
            if (count($project_memberships) > 1) {
                if ($ac_task = $this->getMappedObjectType($this->mapping_table_name, $task_id, 'Task')) {
                    $body = '[This is a copy of the task ' . $ac_task->getViewUrl() . ' ]';
                }
            }

            $assignee = null;
            if (!is_null($task->assignee)) {
                $assignee = $this->getUser($task->assignee->gid);
                $project->addMembers([$assignee], ['send_invitations' => $this->send_emails]);
                $assignee = $assignee->getId();
            }

            $additional_name = '';
            if (!is_null($task->parent)) {
                $ac_task = $this->getMappedObjectType($this->mapping_table_name, $task->parent->gid, 'Task');

                if (!empty($ac_task)) {
                    $body .= '[SUBTASK of task: ' . $ac_task->getViewUrl() . ' ]';
                }
                $additional_name = '[SUBTASK]';

                // If task does not belong to any section, inherited section from parent
                if (is_null($section_id)) {
                    $section_id = $parent_section;
                }
            }

            $client = new EmojiClient(new Ruleset());
            $client->imageType = 'svg';

            $custom_field = !empty($task->custom_fields) ? $this->getCustomFields($task->custom_fields, $body . $task->notes) : '';

            //params to create task
            $task_name = $additional_name . ' ' . $this->maxLength(trim($task->name));
            $params = [
                'name' => $this->maxLength($task_name),
                'body' => Markdown::defaultTransform($body . ' ' . $client->toImage($this->importMentions($task->notes, $project)) . ' ' . $custom_field),
                'assignee_id' => $assignee,
                'project_id' => $project->getId(),
                'created_on' => new DateTimeValue($task->created_at),
                'updated_on' => $task->modified_at ? new DateTimeValue($task->modified_at) : null,
                'due_on' => $task->due_on ? new DateTimeValue($task->due_on) : null,
                'send_invitations' => $this->send_emails,
                'notify_subscribers' => $this->send_emails,
            ];

            // if task is completed add additional parameters
            if ($task->completed) {
                $params = array_merge(
                    $params,
                    [
                        'completed_on' => new DateTimeValue($task->completed_at),
                    ]
                );
            } else {
                if (!is_null($complited_at)) {
                    $params = array_merge(
                        $params,
                        [
                            'completed_on' => $complited_at,
                        ]
                    );
                }
            }

            // if task belongs to section adding parameter to connect whit task list
            if (!is_null($section_id)) {
                $params = array_merge(
                    $params,
                    [
                        'task_list_id' => array_key_exists($section_id, $sections)
                            ? $sections[$section_id]
                            : TaskLists::getFirstTaskListId($project),
                    ]
                );
            }

            /** @var Task $ac_task */
            $ac_task = Tasks::create($params, false);

            $this->saveTask($ac_task);

            if (!empty($task->custom_fields)) {
                $this->getCustomFields($task->custom_fields, $body . $task->notes, $ac_task->getId());
            }

            $this->importSubscribers($ac_task, $task->followers, $project);
            $this->importTags($ac_task, $task_id);
            $this->importComments($ac_task, $task_id, $project);
            $this->importAttachments($ac_task, $task_id);
            $this->mapObject($this->mapping_table_name, $task_id, $ac_task->getId(), 'Task');
            $this->importSubtasks($ac_task, $task_id, $project, $sections, $section_id, $complited_at);

            return $ac_task;
        }
    }

    /**
     * @param         $fields
     * @param  null   $name
     * @param  null   $ac_task_id
     * @return string
     */
    private function getCustomFields($fields, $name = null, $ac_task_id = null)
    {
        $parse_fields = '';
        if (is_foreachable($fields)) {
            foreach ($fields as $field) {
                if (!empty($field->enum_value)) {
                    $parse_fields .= $field->name . ' = ' . $field->enum_value->name . '<br>';
                    if (!empty($ac_task_id)) {
                        if ($ac_label = $this->getMappedObjectType($this->mapping_table_name, $field->enum_value->gid, 'Label')) {
                            DB::insertRecord('parents_labels', [
                                'parent_type' => 'Task',
                                'parent_id' => $ac_task_id,
                                'label_id' => $ac_label->getId(),
                            ]);
                        }
                    }
                }

                if (!empty($field->number_value)) {
                    $parse_fields .= $field->name . ' = ' . $field->number_value . '<br>';
                }
                if (!empty($field->text_value)) {
                    $parse_fields .= $field->name . ' = ' . $field->text_value . '<br>';
                }
            }

            if ($parse_fields !== '') {
                $new_line = !empty($name) ? '<br><br>' : '';

                $parse_fields = $new_line . '<strong> Custom Fields: </strong><br>' . $parse_fields;
            }
        }

        return $parse_fields;
    }

    /**
     * @param $workspace_id
     * @return int
     * @throws DBQueryError
     * @throws InvalidParamError
     * @throws ValidationErrors
     */
    private function importCustomFieldEnum($workspace_id)
    {
        try {
            $custom_fields = $this->client->custom_fields->findByWorkspace($workspace_id);
        } catch (AsanaError $e) {
            AngieApplication::log()->error('Asana error with custom fields', [
                'message' => $e->getMessage(),
            ]);

            return 0;
        }

        if (empty($custom_fields)) {
            return 0;
        }

        $i = 1;

        try {
            foreach ($custom_fields as $custom_field) {
                if ($custom_field->type === 'enum' && !empty($custom_field->enum_options)) {
                    $enums = $this->client->custom_fields->findById($custom_field->gid)->enum_options;
                    $name_max_length = (new TaskLabel())->getMaxNameLength();

                    foreach ($enums as $enum) {
                        $name = trim($enum->name) != '' ? strtoupper(trim($custom_field->name) . ' : ' . trim($enum->name)) : strtoupper($enum->color);
                        $color = $this->getColorForEnums($enum->color);
                        $name = !empty($name) ? substr_utf($name, 0, $name_max_length) : 'empty';

                        if ($id = DB::executeFirstCell('SELECT id FROM labels WHERE type = ? AND name = ? AND color = ?', 'TaskLabel', $name, $color)) {
                            // set existing label to be global
                            $ac_label = Labels::findById($id);
                            $ac_label->setFieldValue('is_global', true);
                            $ac_label->save();
                        } else {
                            // create not global label
                            if ($id = DB::executeFirstCell('SELECT id FROM labels WHERE type = ? AND name = ?', 'TaskLabel', $name)) {
                                $name = substr_utf($name, 0, $name_max_length - (strlen((string) ($i))));
                                $name .= ' ' . $i;
                                $i++;
                            }

                            $ac_label = Labels::create([
                                'type' => 'TaskLabel',
                                'name' => $name,
                                'color' => $color,
                                'is_global' => false,
                            ]);

                            $this->mapObject($this->mapping_table_name, $enum->gid, $ac_label->getId(), 'Label');
                        }
                    }
                }
            }
        } catch (AsanaError $e) {
            AngieApplication::log()->error('Asana error with single custom field', [
                'message' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * @param      $task_id
     * @param      $sections
     * @param      $section_id
     * @param null $completed_at
     */
    private function importSubtasks(Task $ac_task, $task_id, Project $project, $sections, $section_id, $completed_at = null)
    {
        $subtasks = $this->client->tasks->subtasks($task_id);

        //check if exists subtasks and recursively create tasks
        foreach ($subtasks as $subtask) {
            $detailed_subtask = $this->client->tasks->findById($subtask->gid);
            $assignee = null;
            if (!is_null($detailed_subtask->assignee)) {
                $user = $this->getUser($detailed_subtask->assignee->gid);
                $assignee = $user->getId();
            }

            $link = '';
            if (!$this->checkIfCanBeSubtask($detailed_subtask)) {
                $ac_subtask = $this->importTask($subtask->gid, $project, $sections, $section_id, $completed_at);
                $link = ' link ' . $ac_subtask->getViewUrl();
            }

            $properties = [
                'task_id' => $ac_task->getId(),
                'body' => $this->checkName(trim($detailed_subtask->name)) . $link,
                'assignee_id' => $assignee,
                'created_on' => new DateTimeValue($detailed_subtask->created_at),
                'updated_on' => $detailed_subtask->modified_at ? new DateTimeValue($detailed_subtask->modified_at) : null,
                'send_invitations' => $this->send_emails,
                'notify_subscribers' => $this->send_emails,
                'notify_assignee' => $this->send_emails,
            ];

            if ($detailed_subtask->completed) {
                $properties['completed_on'] = new DateTimeValue($detailed_subtask->completed_at);
            } else {
                if (!is_null($completed_at)) {
                    $properties['completed_on'] = $completed_at;
                }
            }

            Subtasks::create($properties);
        }
    }

    /**
     * Import labels.
     *
     * @param $task_id
     */
    private function importTags(Task $ac_task, $task_id)
    {
        $tags = $this->client->tasks->tags($task_id);

        foreach ($tags as $tag) {
            if ($ac_label = $this->getMappedObjectType($this->mapping_table_name, $tag->gid, 'Label')) {
                DB::insertRecord('parents_labels', [
                    'parent_type' => 'Task',
                    'parent_id' => $ac_task->getId(),
                    'label_id' => $ac_label->getId(),
                ]);
            }
        }
    }

    private function importSubscribers(Task $ac_task, array $followers, Project $project)
    {
        foreach ($followers as $follower) {
            $user = $this->getUser($follower->gid);
            $ac_task->subscribe($user);
            $project->addMembers([$user], ['send_invitations' => $this->send_emails]);
        }
    }

    /**
     * @param $name
     * @return string
     */
    private function checkName($name)
    {
        if (!is_null($name) && ($name !== '')) {
            return $name;
        }

        return 'Untitled subtask';
    }

    /**
     * @param $task_id
     */
    private function importComments(Task $task, $task_id, Project $project)
    {
        $comments = $this->getCommentsFromTask($task_id);
        foreach ($comments as $comment) {
            if ($comment->type === 'comment') {
                /** @var User $user */
                $user = $this->getMappedObjectType($this->mapping_table_name, $comment->created_by->gid, 'User');

                $additional_params = [];
                $formatted_attachments = [];

                $additional_params['created_on'] = new DateTimeValue($comment->created_at);
                $additional_params['notify_subscribers'] = $this->send_emails;

                if (empty($user)) {
                    $user = Users::findByEmail('unknown@exmaple.com');

                    if (!$user instanceof Users) {
                        $user = new AnonymousUser($comment->created_by->name, 'unknown@exmaple.com');
                    }
                }

                // client for handle emoji
                $client = new EmojiClient(new Ruleset());
                $client->imageType = 'svg';

                $new_comment = $this->importMentions($comment->text, $project);

                if (strlen($new_comment) > 0) {
                    $body = $new_comment ? Markdown::defaultTransform($client->toImage($new_comment)) : '<i>No comment</i>';

                    try {
                        $task->submitComment($body, $user, $additional_params);
                    } catch (Exception $e) {
                        AngieApplication::log()->warning('Comment skipped during Asana import', [
                            'comment_body' => $body,
                            'exception' => $e,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @param         $coment_text
     * @return string
     */
    private function importMentions($coment_text, Project $ac_project)
    {
        $has_mention = strpos($coment_text, 'https://app.asana.com/0/');
        if ($has_mention != false || $has_mention === 0) {
            $lines = explode(PHP_EOL, $coment_text);
            $new_comment = '';
            foreach ($lines as $line) {
                $position = strpos($line, 'https://app.asana.com/0/');
                if ($position != false || $position === 0) {
                    $words = explode(' ', $line);
                    $i = 0;
                    foreach ($words as $word) {
                        if (count($words) === ($i + 1)) {
                            $space = PHP_EOL;
                        } else {
                            $space = ' ';
                        }
                        if (strpos($word, 'https://app.asana.com/0/') === 0) {
                            $split_word = explode('/', $word);
                            $id = (int) $split_word[count($split_word) - 1];

                            try {
                                $project = $this->client->projects->findById($id);

                                $id = null;
                                $name = null;

                                if (!empty($project->owner)) {
                                    $id = $project->owner->gid;
                                    $name = $project->owner->name;
                                } elseif (!empty($project->followers[0])) {
                                    $id = $project->followers[0]->gid;
                                    $name = $project->followers[0]->name;
                                }

                                /** @var User $mented_user */
                                $mented_user = $this->getMappedObjectType($this->mapping_table_name, $id, 'User');

                                if (!empty($mented_user)) {
                                    $new_comment .= $this->createMentions($mented_user->getId(), $mented_user->getName()) . $space;
                                    $ac_project->addMembers([$mented_user], ['send_invitations' => $this->send_emails]);
                                } else {
                                    if (!empty($name)) {
                                        $new_comment .= $this->createMentions(0, $name) . $space;
                                    }
                                }
                            } catch (Exception $e) {
                                AngieApplication::log()->warning('Mention skipped during Asana import', [
                                    'exception' => $e,
                                ]);
                            }
                        } else {
                            $new_comment .= $word . $space;
                        }
                        $i++;
                    }
                } else {
                    $new_comment .= $line . PHP_EOL;
                }
            }

            return $new_comment;
        }

        return $coment_text;
    }

    /**
     * @param $id
     * @param $name
     * @return string
     */
    private function createMentions($id, $name)
    {
        return '<span class="new_mention" data-user-id="' . $id . '">' . $name . '</span>';
    }

    /**
     * @param $task_id
     * @return mixed
     */
    private function getAttachments($task_id)
    {
        $path = sprintf('/tasks/%s/attachments', $task_id);

        return $this->client->request('GET', $path, []);
    }

    /**
     * @param $task_id
     */
    private function importAttachments(Task $task, $task_id)
    {
        $attachments = $this->getAttachments($task_id);

        foreach ($attachments as $attachment) {
            $path = sprintf('/attachments/%s', $attachment->gid);
            $attach = $this->client->request('GET', $path, []);

            $user = $this->getUser($attach->gid);

            if ($attach->host === 'asana') {
                AngieApplication::jobs()->dispatch(
                    new AsanaAttachmentsImport([
                        'instance_id' => AngieApplication::getAccountId(),
                        'instance_type' => 'feather',
                        'priority' => JobInterface::HAS_HIGHEST_PRIORITY - 1,
                        'delay' => 60,
                        'attempts' => 5,
                        'context_type' => Task::class,
                        'context_id' => $task->getId(),
                        'attachment_id' => $attach->gid,
                        'mime_type' => 'application/octet-stream',
                        'tasks_path' => ENVIRONMENT_PATH . '/tasks',
                        'user_id' => $user->getId(),
                    ]),
                    static::DOWNLOAD_FILE_CHANNEL
                );
            }

            if ($attach->host === 'dropbox') {
                /** @var DropboxAttachment $attachment */
                $attachment = new DropboxAttachment();
                $attachment->setParent($task);
                $attachment->setUrl($attach->download_url);
                $attachment->setCreatedBy($user);
                $attachment->setName($attach->name);
                $attachment->save();
            }

            if ($attach->host === 'gdrive') {
                /** @var GoogleDriveAttachment $attachment */
                $attachment = new GoogleDriveAttachment();
                $attachment->setParent($task);
                $attachment->setName($attach->name);
                $attachment->setUrl($attach->download_url);
                $attachment->setCreatedBy($user);
                $attachment->save();
            }
        }
    }

    /**
     * @param $asana_user_id
     * @return DbResult|object|Owner|null
     */
    private function getUser($asana_user_id)
    {
        if (is_object($asana_user_id)) {
            $asana_user_id = $asana_user_id->gid;
        }
        $user = $this->getMappedObjectType($this->mapping_table_name, $asana_user_id, 'User');
        if ($user instanceof User) {
            return $user;
        } else {
            return Users::findFirstOwner();
        }
    }

    /**
     * @param $url
     * @return string|null
     */
    private function downloadAttachment($url)
    {
        $ac_tmp_attachments = WORK_PATH . '/' . AngieApplication::getAccountId() . '-asana_attachments';
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

        $response = $this->http_client->get($url, $request_options);
        $code = $response->getStatusCode();

        if (!is_file($filename)){
            AngieApplication::log()->warning('Attachment skipped during Asana import', [
                'comment_body' => 'Attachment skipped because it`s not file:' . $response->getStatusCode() . 'url: ' . $url,
            ]);

            return null;
        } elseif (($code < 200) || ($code >= 300)) {
            AngieApplication::log()->warning('Attachment skipped during Asana import', [
                'comment_body' => 'Attachment skipped because ' . $response->getStatusCode() . 'url: ' . $url,
            ]);

            return null;
        }

        return $filename;
    }

    /**
     * @return $this
     */
    public function &startOver()
    {
        $this->setAccessToken(null);
        $this->setSelectedWorkspaces(null);
        $this->setActiveWorkspaces(null);

        return parent::startOver();
    }

    /**
     * @return $this
     */
    public function invite()
    {
        return $this->inviteUsers($this->mapping_table_name);
    }
}
