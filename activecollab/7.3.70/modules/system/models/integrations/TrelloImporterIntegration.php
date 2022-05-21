<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use Emojione\Client as EmojiClient;
use Emojione\Ruleset;
use Michelf\Markdown;
use Trello\Trello;

class TrelloImporterIntegration extends AbstractImporterIntegration
{
    const DAYS_BEFORE_ACCESS_TOKEN_EXPIRE = '30days';

    protected $oauth;
    protected $client_service;

    /**
     * @var string
     */
    private $mapping_table_name = 'trello_migration_mappings';

    /**
     * Api key credential.
     *
     * @var string
     */
    private $api_key;

    /**
     * Api key secret credential.
     *
     * @var string
     */
    private $api_key_secret;

    /**
     * Access Token credential.
     *
     * @var string
     */
    private $access_token;

    /**
     * Access Token secret credential.
     *
     * @var string
     */
    private $access_token_secret;

    /**
     * Http Client.
     *
     * @var GuzzleHttp\Client
     */
    private $http_client = false;

    /**
     * Return true if access to trello is valid.
     *
     * @return bool
     */
    public function hasValidAccess()
    {
        return $this->getAccessToken() && $this->getAccessTokenSecret() &&
        $this->getAuthorizedOn()->daysBetween(DateValue::now()) < str_replace('days', '', self::DAYS_BEFORE_ACCESS_TOKEN_EXPIRE);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Trello Importer';
    }

    public function getShortName(): string
    {
        return 'trello-importer';
    }

    protected function getLogEventPrefix(): string
    {
        return 'trello';
    }

    /**
     * @return string|null
     */
    public function getConsumerKey()
    {
        return defined('TRELLO_CONSUMER_KEY') ? TRELLO_CONSUMER_KEY : $this->getAdditionalProperty('consumer_key');
    }

    /**
     * @param string $consumer_key
     *
     * @return string|null
     */
    public function setConsumerKey($consumer_key)
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setAdditionalProperty('consumer_key', $consumer_key);
        }

        return self::getConsumerKey();
    }

    /**
     * @return string|null
     */
    public function getConsumerKeySecret()
    {
        return defined('TRELLO_CONSUMER_KEY_SECRET') ? TRELLO_CONSUMER_KEY_SECRET : $this->getAdditionalProperty('consumer_key_secret');
    }

    /**
     * @param string $consumer_key_secret
     *
     * @return string|null
     */
    public function setConsumerKeySecret($consumer_key_secret)
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setAdditionalProperty('consumer_key_secret', $consumer_key_secret);
        }

        return self::getConsumerKeySecret();
    }

    /**
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->getAdditionalProperty('access_token');
    }

    /**
     * @param string $access_token
     *
     * @return mixed
     */
    public function setAccessToken($access_token)
    {
        return $this->setAdditionalProperty('access_token', $access_token);
    }

    /**
     * @return string|null
     */
    public function getAccessTokenSecret()
    {
        return $this->getAdditionalProperty('access_token_secret');
    }

    /**
     * @param string $access_token_secret
     *
     * @return mixed
     */
    public function setAccessTokenSecret($access_token_secret)
    {
        return $this->setAdditionalProperty('access_token_secret', $access_token_secret);
    }

    /**
     * Get request token.
     *
     * @return string
     */
    public function getRequestToken()
    {
        return $this->getAdditionalProperty('request_token');
    }

    /**
     * Set request token.
     *
     * @param  string $request_token
     * @return mixed
     */
    public function setRequestToken($request_token)
    {
        return $this->setAdditionalProperty('request_token', $request_token);
    }

    /**
     * Get request token secret.
     *
     * @return string
     */
    public function getRequestTokenSecret()
    {
        return $this->getAdditionalProperty('request_token_secret');
    }

    /**
     * Set request token secret.
     *
     * @param  string $request_token_secret
     * @return mixed
     */
    public function setRequestTokenSecret($request_token_secret)
    {
        return $this->setAdditionalProperty('request_token_secret', $request_token_secret);
    }

    /**
     * Get authorized on.
     *
     * @return DateTimeValue
     */
    public function getAuthorizedOn()
    {
        return DateTimeValue::makeFromTimestamp($this->getAdditionalProperty('authorized_on', 0));
    }

    /**
     * Set authorized on.
     */
    public function setAuthorizedOn()
    {
        $this->setAdditionalProperty('authorized_on', DateTimeValue::now()->getTimestamp());
    }

    /**
     * @return string
     */
    private function getCallbackUrl()
    {
        return ROOT_URL . '/integrations/trello-importer';
    }

    /**
     * Set trello credentials.
     *
     * @param $api_key
     * @param $api_key_secret
     * @param $access_token
     * @param $access_token_secret
     */
    public function setCredentials($api_key, $api_key_secret, $access_token, $access_token_secret)
    {
        $this->api_key = $api_key;
        $this->api_key_secret = $api_key_secret;
        $this->access_token = $access_token;
        $this->access_token_secret = $access_token_secret;
    }

    /**
     * @param int $active_projects
     *
     * @return mixed
     */
    public function setActiveProjects($active_projects)
    {
        return $this->setAdditionalProperty('active_projects', $active_projects);
    }

    /**
     * @return int|null
     */
    public function getActiveProjects()
    {
        return $this->getAdditionalProperty('active_projects');
    }

    /**
     * @param array $trello_users
     *
     * @return mixed
     */
    public function setTrelloUsers($trello_users)
    {
        return $this->setAdditionalProperty('trello_users', $trello_users);
    }

    /**
     * @return array|null
     */
    public function getTrelloUsers()
    {
        return $this->getStatus() == self::STATUS_NORMAL ? $this->getAdditionalProperty('trello_users') : null;
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
            'active_projects' => $this->getActiveProjects(),
            'trello_users' => $this->getTrelloUsers(),
        ]);
    }

    /**
     * Create array with distinct trello users.
     *
     * @param array  $trello_users
     * @param string $trello_board_id
     */
    private function getUsersForImport(& $trello_users, $trello_board_id)
    {
        $board_members = json_decode(json_encode($this->clientService()->boards->get($trello_board_id . '/members')), true);

        foreach ($board_members as $board_member) {
            /** @var User $user */
            $user = $this->getMappedObjectType($this->mapping_table_name, $board_member['id'], 'User');

            if (empty($user) && !in_array($board_member, $trello_users)) {
                $trello_users[] = $board_member;
            }
        }
    }

    /**
     * Validate supplied credentials.
     *
     * @return array
     * @throws Exception
     */
    public function validateCredentials()
    {
        // try to fetch user boards
        try {
            $boards = $this->clientService()->members->get('my/boards');
        } catch (Exception $e) {
            throw new Exception('Authentication Failed');
        }

        $count = 0;
        $trello_users = [];
        if (is_foreachable($boards)) {
            foreach ($boards as $board) {
                ++$count;
                $this->getUsersForImport($trello_users, $board->id);
            }
        }

        if (count($trello_users) === 0) {
            $trello_users = null;
        }

        // save count of active projects and trello users
        $this->setActiveProjects($count);
        $this->setTrelloUsers($trello_users);
        $this->save();

        return $this;
    }

    /**
     * Return client service.
     *
     * @return \Trello\Trello
     */
    public function clientService()
    {
        if (!$this->client_service instanceof Trello) {
            $this->client_service = new Trello($this->getConsumerKey(), $this->getConsumerKeySecret(),
                $this->getAccessToken(), $this->getAccessTokenSecret());
        }

        return $this->client_service;
    }

    /**
     * Return OAuth adapter.
     *
     * @return \Trello\Trello
     */
    public function oauth()
    {
        if (!$this->oauth instanceof Trello) {
            $this->oauth = new Trello($this->getConsumerKey(), $this->getConsumerKeySecret());
        }

        return $this->oauth;
    }

    /**
     * Get request url.
     *
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->oauth()->authorize([
            'name' => 'Active Collab',
            'redirect_uri' => $this->getCallbackUrl(),
            'expiration' => self::DAYS_BEFORE_ACCESS_TOKEN_EXPIRE,
            'scope' => [
                'read' => true,
                'write' => false,
                'account' => false,
            ],
        ], true);
    }

    /**
     * Authorize with Trello.
     *
     * @return $this
     */
    public function authorize(array $params)
    {
        $_GET['oauth_token'] = array_var($params, 'oauth_token');
        $_GET['oauth_verifier'] = array_var($params, 'oauth_verifier');

        if ($this->oauth()->authorize()) {
            $this->setAccessToken($this->oauth()->token());
            $this->setAccessTokenSecret($this->oauth()->oauthSecret());
            $this->setAuthorizedOn();
            $this->save();
        }

        return $this;
    }

    /**
     * @param array $users
     *
     * @return $this
     */
    public function checkAndPrepareTrelloUsers($users)
    {
        $trello_users = $this->getTrelloUsers();

        if (!is_null($trello_users)) {
            $new_trello_users = [];
            foreach ($trello_users as $trello_user) {
                if (array_key_exists($trello_user['id'], $users)) {
                    $value = $users[$trello_user['id']]; // posted ac id or new email
                    $is_value_valid_email = false;

                    /* @var User $ac_user */
                    if (!empty($value) && filter_var($value, FILTER_VALIDATE_INT)) {
                        $ac_user = Users::findById($value);
                    } else {
                        if (!empty($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            /** @var User $user */
                            if ($user = Users::findByEmail($value)) {
                                $ac_user = $user;
                            } else {
                                $ac_user = null;
                                $is_value_valid_email = true;
                            }
                        } else {
                            $ac_user = null;
                        }
                    }

                    if (empty($ac_user)) {
                        if ($is_value_valid_email) {
                            $trello_user['email'] = $value;
                        } else {
                            $trello_user['email'] = 'trello+' . $trello_user['username'] . '@example.com';
                        }
                        $trello_user['ac_id'] = null;
                    } else {
                        $trello_user['email'] = $ac_user->getEmail();
                        $trello_user['ac_id'] = $ac_user->getId();
                    }
                } else {
                    $trello_user['email'] = 'trello+' . $trello_user['username'] . '@example.com';
                    $trello_user['ac_id'] = null;
                }

                $new_trello_users[] = $trello_user;
            }

            $this->setTrelloUsers($new_trello_users);
            $this->save();
        }

        return $this;
    }

    /**
     * Import trello users before import process.
     *
     * @return $this|$users
     */
    public function importTrelloUsers()
    {
        $users = $this->getTrelloUsers();

        if (!is_null($users)) {
            foreach ($users as $user) {
                $this->importUser($user);
            }
        }

        return $this;
    }

    /**
     * Import user.
     *
     * @param $trello_user
     * @return DataObject|User
     * @throws Exception
     * @throws FileCopyError
     * @throws InvalidParamError
     */
    private function importUser($trello_user)
    {
        if (isset($trello_user['ac_id']) && !is_null($trello_user['ac_id'])) {
            $user = Users::findById($trello_user['ac_id']);
            $this->mapObject($this->mapping_table_name, $trello_user['id'], $trello_user['ac_id'], 'User');
        } else {
            $t_user = $this->clientService()->members->get($trello_user['id']);

            $full_name = $t_user->fullName;
            $full_name_arr = explode(' ', $full_name);

            if (!empty($t_user->avatarHash)) {
                $temporary_file = $this->downloadAttachment(
                    'https://trello-members.s3.amazonaws.com/' . $trello_user['id'] . '/' . $t_user->avatarHash . '/170.png'
                );

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
                } else {
                    $avatar_code = '';
                }
            } else {
                $avatar_code = '';
            }

            $user = Users::create([
                'type' => Member::class,
                'email' => $trello_user['email'],
                'first_name' => isset($full_name_arr[0]) ? $full_name_arr[0] : null,
                'last_name' => isset($full_name_arr[1]) ? $full_name_arr[1] : null,
                'company_id' => 0,
                'password' => AngieApplication::authentication()->generateStrongPassword(32),
                'uploaded_avatar_code' => $avatar_code,
            ]);

            // disable morning mail for all members
            ConfigOptions::setValueFor(['notifications_user_send_morning_paper' => false], $user);

            $this->mapObject($this->mapping_table_name, $trello_user['id'], $user->getId(), 'User');
        }

        return $user;
    }

    /**
     * Schedule import process.
     *
     * @return TrelloImporterIntegration
     */
    public function &scheduleImport()
    {
        $command_arguments = [
            $this->getConsumerKey(), $this->getConsumerKeySecret(),
            $this->getAccessToken(), $this->getAccessTokenSecret(),
        ];

        $this->dispatchJob(
            [
                'command' => 'import_trello_account',
                'command_arguments' => $command_arguments,
                'log_output_to_file' => AngieApplication::getAvailableWorkFileName('trello-import-' . date('Y-m-d H-i-s'), 'txt', null),
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

        // update trello mapping table if exists
        $this->migrateMappingTable($this->mapping_table_name);

        // import all boards
        return $this->importBoards($output);
    }

    /**
     * Import all boards.
     *
     * @param  callable|null $output
     * @throws Exception
     */
    private function importBoards(callable $output)
    {
        // set status to importing boards
        $this->setStatus(self::STATUS_IMPORTING);
        $this->save();

        $boards = $this->clientService()->members->get('my/boards');

        // check if maybe we don't have boards
        if (!count($boards)) {
            $this->setStatus(self::STATUS_FAILED);
            $this->save();

            throw new Exception('There are no boards in this Trello account');
        }

        $this->setImportTotal($this->getActiveProjects());
        $this->setImportProgress(0);
        $this->save();

        $this->outputBuffer($output, [
            "****************************************************************\r\n",
            '***** There is ' . count($boards) . " active board(s) to import. Importing ... *****\r\n",
            "****************************************************************\r\n",
        ]);

        $this->outputBuffer($output, 'Importing teams...');
        $this->importTeams();
        $this->outputBuffer($output, "Teams are imported.\r\n");

        $i = 1;
        foreach ($boards as $board) {
            $this->setImportProgress($i);
            $this->setImportLabel($board->name);
            $this->save();

            if (!$this->getMappedObjectType($this->mapping_table_name, $board->id, 'Board')) {
                $this->outputBuffer($output, [
                    "----------------------------------------- ONE BOARD\r\n",
                    "Importing board: '" . $board->name . "'\r\n",
                ]);

                $this->importBoard($board->id, $output);

                $this->outputBuffer($output, [
                    "Board '" . $board->name . "' is imported.\r\n",
                    "----------------------------------------- END OF ONE BOARD\r\n",
                ]);
            } else {
                if ($output) {
                    $this->outputBuffer($output, "Board '" . $board->name . "' already imported. Skipped. ...\r\n");
                }
            }

            ++$i;
        }

        $this->setStatus(self::STATUS_INVITING);
        $this->save();
    }

    /**
     * Import teams.
     */
    private function importTeams()
    {
        $teams = $this->clientService()->members->get('my/organizations');

        if (is_foreachable($teams)) {
            $team_users_batch = new DBBatchInsert('team_users', ['team_id', 'user_id'], 500, DBBatchInsert::REPLACE_RECORDS);

            foreach ($teams as $team) {
                $ac_team = $this->getMappedObjectType($this->mapping_table_name, $team->id, 'Team');

                if (empty($ac_team)) {
                    $trello_team = $this->clientService()->organizations->get($team->id, ['members' => 'all']);

                    if (!empty($trello_team->members)) {
                        $team_name = $this->maxLength($trello_team->displayName, 100);

                        $existing_team_name = DB::executeFirstCell(
                            'SELECT name FROM teams WHERE LOWER(name) = ?',
                            strtolower($team_name)
                        );

                        if (!empty($existing_team_name)) {
                            $team_name .= ' (Trello)';
                        }

                        /** @var Team $created_team */
                        $created_team = Teams::create([
                            'name' => $team_name,
                            'created_on' => $this->getCreateDateFromTrelloId($team->id),
                        ]);

                        foreach ($trello_team->members as $member) {
                            $team_users_batch->insert($created_team->getId(), $this->getUser($member->id)->getId());
                        }

                        $this->mapObject($this->mapping_table_name, $trello_team->id, $created_team->getId(), 'Team');
                    }
                }
            }

            $team_users_batch->done();
        }
    }

    /**
     * Import board by Id.
     *
     * @param callable|null $output
     * @param int           $trello_board_id
     */
    private function importBoard($trello_board_id, callable $output)
    {
        $params = ['labels' => 'all', 'fields' => 'name,desc,dateLastActivity,starred,closed'];

        if ($trello_board = $this->clientService()->boards->get($trello_board_id, $params)
        ) {
            $this->outputBuffer($output, 'Get owner...');
            $owners = $this->clientService()->boards->get($trello_board_id . '/members', ['filter' => 'owners']);
            $creator = $this->getUser($owners[0]); // creator is first owner
            $this->outputBuffer($output, "---> Owner is imported.\r\n");

            $project_members = [$creator->getId()];

            $this->outputBuffer($output, 'Get members...');
            $members = $this->clientService()->boards->get($trello_board_id . '/members');
            foreach ($members as $member) {
                $project_member_id = $this->getUser($member)->getId();

                if ($project_member_id && !in_array($project_member_id, $project_members)) {
                    array_push($project_members, $project_member_id);
                    $project_members[] = $project_member_id;
                }
            }
            $this->outputBuffer($output, "---> Members is imported.\r\n");

            $created_project = Projects::create(
                [
                    'name' => $this->maxLength($trello_board->name),
                    'body' => $trello_board->desc,
                    'company_id' => AngieApplication::getContainer()
                            ->get(OwnerCompanyResolverInterface::class)
                                ->getId(),
                    'created_on' => $this->getCreateDateFromTrelloId($trello_board->id),
                    'created_by_id' => $creator->getId(),
                    'created_by_name' => $creator->getFirstName() . ' ' . $creator->getLastName(),
                    'created_by_email' => $creator->getEmail(),
                    'members' => $project_members,
                    'send_invitations' => $this->send_emails,
                    'skip_default_task_list' => true,
                ]
            );

            $this->outputBuffer($output, 'Get board labels... ');
            $this->importBoardLabels($trello_board->labels);
            $this->outputBuffer($output, "---> Board labels are imported.\r\n");

            $this->outputBuffer($output, 'Get lists... ');
            $this->importLists($created_project, $trello_board_id);
            $this->outputBuffer($output, "---> Lists are imported.\r\n");

            if ($trello_board->starred) {
                Favorites::addToFavorites($created_project, $creator);
            }

            if ($trello_board->closed == true) {
                $this->completeObject($created_project, $creator, new DateTimeValue($trello_board->dateLastActivity), true);
            }

            $this->mapObject($this->mapping_table_name, $trello_board_id, $created_project->getId(), 'Board');
        }
    }

    /**
     * Check to see if we imported user already, if not import him.
     *
     * @param  object|int $trello_user_id
     * @return User
     */
    private function getUser($trello_user_id)
    {
        if (is_object($trello_user_id)) {
            $trello_user_id = $trello_user_id->id;
        }

        if ($user = $this->getMappedObjectType($this->mapping_table_name, $trello_user_id, 'User')) {
            return $user;
        } else {
            return Users::findFirstOwner();
        }
    }

    /**
     * Import board labels.
     *
     * @param $trello_labels
     */
    private function importBoardLabels($trello_labels)
    {
        if (is_foreachable($trello_labels)) {
            $i = 1;
            foreach ($trello_labels as $label) {
                if (!empty($label->color)) {
                    $name = $label->name != '' ? strtoupper($label->name) : strtoupper($label->color);
                    $color = $this->getColor($label->color);

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

                        $this->mapObject($this->mapping_table_name, $label->id, $ac_label->getId(), 'Label');
                    }
                }
            }
        }
    }

    /**
     * Get color.
     *
     * @param  string $trello_color
     * @return string $ac_color
     */
    private function getColor($trello_color)
    {
        $ac_color = null;

        switch ($trello_color) {
            case 'red':
                $ac_color = '#FF9C9C';

                break;
            case 'yellow':
                $ac_color = '#FDF196';

                break;
            case 'purple':
                $ac_color = '#FBD6E7';

                break;
            case 'lime':
                $ac_color = '#BDF7FD';

                break;
            case 'green':
                $ac_color = '#C3E799';

                break;
            case 'orange':
                $ac_color = '#FBBB75';

                break;
            case 'blue':
                $ac_color = '#BEACF9';

                break;
            case 'black':
                $ac_color = '#CACACA';

                break;
            case 'sky':
                $ac_color = '#BEEAFF';

                break;
            case 'pink':
                $ac_color = '#FBD6E7';

                break;
        }

        return $ac_color;
    }

    /**
     * Get when trello object was created.
     * More information: http://help.trello.com/article/759-getting-the-time-a-card-or-board-was-created.
     *
     * @param  string $trello_id
     * @return date
     */
    private function getCreateDateFromTrelloId($trello_id)
    {
        return date('Y-m-d h:i:s', hexdec(substr($trello_id, 0, 8)));
    }

    /**
     * Import lists.
     *
     * @param int $trello_board_id
     */
    private function importLists(Project $ac_project, $trello_board_id)
    {
        $trello_lists = $this->clientService()->boards->get($trello_board_id . '/lists', ['cards' => 'all']);

        if (is_foreachable($trello_lists)) {
            foreach ($trello_lists as $list) {
                /** @var TaskList $task_list */
                $task_list = TaskLists::create([
                    'project_id' => $ac_project->getId(),
                    'name' => $this->maxLength($list->name),
                    'created_on' => $this->getCreateDateFromTrelloId($list->id),
                ]);

                // import cards from list
                $this->importCards($task_list, $list->cards);
            }
        }
    }

    /**
     * Import cards.
     *
     * @param array $trello_cards
     */
    private function importCards(TaskList $ac_task_list, $trello_cards)
    {
        if (is_foreachable($trello_cards)) {
            $card_labels_batch = new DBBatchInsert('parents_labels', ['parent_type', 'parent_id', 'label_id'], 500, DBBatchInsert::REPLACE_RECORDS);

            foreach ($trello_cards as $card) {
                if (count($card->idMembers)) {
                    $creator = $this->getUser($card->idMembers[0]);
                    $assignee_id = $creator->getId();
                } else {
                    $creator = Users::findFirstOwner();
                    $assignee_id = 0;
                }

                $postion = !empty($card->pos) && strlen($card->pos) <= 10
                    ? intval($card->pos)
                    : Tasks::findNextPositionInTaskList($ac_task_list);

                /** @var Tasks $task */
                $task = Tasks::create([
                    'name' => $this->maxLength($card->name),
                    'body' => Markdown::defaultTransform($card->desc),
                    'task_list_id' => $ac_task_list->getId(),
                    'assignee_id' => $assignee_id,
                    'project_id' => $ac_task_list->getProjectId(),
                    'created_on' => $this->getCreateDateFromTrelloId($card->id),
                    'created_by_id' => $creator->getId(),
                    'created_by_name' => $creator->getFirstName() . ' ' . $creator->getLastName(),
                    'created_by_email' => $creator->getEmail(),
                    'updated_on' => new DateTimeValue($card->dateLastActivity),
                    'due_on' => is_null($card->due) ? null : new DateTimeValue($card->due),
                    'position' => $postion,
                    'notify_subscribers' => $this->send_emails,
                ], false);

                $this->saveTask($task);

                $this->importSubscribers($task, $card->idMembers);
                $this->importLabels($task, $card->idLabels, $card_labels_batch);
                $this->importChecklists($task, $card->id);
                $this->importComments($task, $card->id);
                $this->importAttachments($task, $card->id);

                if ($card->closed == true) {
                    $this->completeObject($task, $creator, new DateTimeValue($card->dateLastActivity), true);
                }
            }

            $card_labels_batch->done();
        }
    }

    /**
     * Subscribe users from "members" block to task.
     *
     * @param $trello_members
     */
    private function importSubscribers(ISubscriptions $task, $trello_members)
    {
        if (is_foreachable($trello_members) && $task instanceof ISubscriptions) {
            $subscribers = [];

            foreach ($trello_members as $subscriber => $id) {
                $subscribers[] = $this->getUser($id);
            }
            $task->setSubscribers($subscribers);
        }
    }

    /**
     * Import task labels.
     *
     * @param $trello_labels_ids
     */
    private function importLabels(Task $task, $trello_labels_ids, DBBatchInsert & $card_labels_batch)
    {
        if (is_foreachable($trello_labels_ids)) {
            foreach ($trello_labels_ids as $label => $id) {
                if ($ac_label = $this->getMappedObjectType($this->mapping_table_name, $id, 'Label')) {
                    $card_labels_batch->insert('Task', $task->getId(), $ac_label->getId());
                }
            }
        }
    }

    /**
     * Import Comments.
     *
     * @param string $trello_card_id
     */
    private function importChecklists(Task $parent, $trello_card_id)
    {
        $trello_checklists = $this->clientService()->cards->get($trello_card_id . '/checklists');

        if (is_foreachable($trello_checklists)) {
            // order checklists by position
            usort($trello_checklists, function ($a, $b) {
                return $a->pos - $b->pos;
            });

            $i = 1;
            $list_count = count($trello_checklists);
            foreach ($trello_checklists as $checklist) {
                if (isset($checklist->checkItems) && is_foreachable($checklist->checkItems)) {
                    // order items by position
                    usort($checklist->checkItems, function ($a, $b) {
                        return $a->pos - $b->pos;
                    });

                    $j = 0;
                    foreach ($checklist->checkItems as $item) {
                        $properties = [
                            'task_id' => $parent->getId(),
                            'body' => $list_count > 1 ? $checklist->name . ' / ' . $item->name : $item->name,
                            'position' => $i . $j,
                        ];

                        if ($item->state == 'complete') {
                            $user = Users::findFirstOwner();
                            $date = new DateValue();

                            $properties['completed_on'] = $date->now();
                            $properties['completed_by_id'] = $user->getId();
                            $properties['completed_by_name'] = $user->getName();
                            $properties['completed_by_email'] = $user->getEmail();
                        }

                        Subtasks::create($properties);
                        ++$j;
                    }
                }
                ++$i;
            }
        }
    }

    /**
     * Import Comments.
     *
     * @param string $trello_card_id
     */
    private function importComments(IComments $parent, $trello_card_id)
    {
        $trello_comments = $this->clientService()->cards->get($trello_card_id . '/actions', ['filter' => 'commentCard']);

        if (is_foreachable($trello_comments)) {
            foreach ($trello_comments as $comment) {
                $user = $this->getUser($comment->idMemberCreator);
                $additional_params = [];
                $formatted_attachments = [];

                $additional_params['created_on'] = new DateTimeValue($comment->date);
                $additional_params['notify_subscribers'] = $this->send_emails;

                // client for handle emoji
                $client = new EmojiClient(new Ruleset());
                $client->imageType = 'svg';

                $body = $comment->data->text ? Markdown::defaultTransform($client->toImage($comment->data->text)) : '<i>No comment</i>';

                try {
                    $parent->submitComment($body, $user, $additional_params);
                } catch (Exception $e) {
                    AngieApplication::log()->warning('Comment skipped during Trello import', [
                        'comment_body' => $body,
                        'exception' => $e,
                    ]);
                }
            }
        }
    }

    /**
     * Import Attachments.
     *
     * @param string $trello_card_id
     */
    private function importAttachments(IAttachments $parent, $trello_card_id)
    {
        $trello_attachments = $this->clientService()->cards->get($trello_card_id . '/attachments');

        if (is_foreachable($trello_attachments)) {
            foreach ($trello_attachments as $attachment) {
                if ($this->checkFile($attachment)) {
                    continue;
                }

                $path = $this->downloadAttachment($attachment->url);
                $filename = $this->maxLength($attachment->name);
                $type = is_null($attachment->mimeType) ? 'application/octet-stream' : $attachment->mimeType;
                $user = $this->getUser($attachment->idMember);

                $parent->attachFile($path, $filename, $type, $user);
            }
        }
    }

    /**
     * Skip Google, Dropbox, Box, OneDrive documents and check file size.
     *
     * @param $trello_attachment
     * @return bool
     */
    private function checkFile($trello_attachment)
    {
        if ((isset($trello_attachment->isUpload) && !$trello_attachment->isUpload) ||
            (isset($trello_attachment->bytes) && $trello_attachment->bytes == 0)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Download a single attachment file.
     *
     * @param  string $url
     * @return string
     */
    private function downloadAttachment($url)
    {
        $ac_tmp_attachments = WORK_PATH . '/' . AngieApplication::getAccountId() . '-trello_attachments';
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
            'Authorization' => "OAuth oauth_consumer_key=\"{$this->getConsumerKey()}\", oauth_token=\"{$this->getAccessToken()}\"",
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
        try {
            $this->http_client->get($url, $request_options);
        } catch (Throwable $e) {
            AngieApplication::log()->error(
                'Fail to download Trello file',
                [
                    'exception' => $e,
                    'url' => $url,
                ]
            );
            // Try to download file without authorization (members avatar)
            unset($request_options['headers']['Authorization']);
            $this->http_client->get($url, $request_options);
        }

        return $filename;
    }

    public function &startOver()
    {
        $this->setAccessToken(null);
        $this->setAccessTokenSecret(null);
        $this->setTrelloUsers(null);

        return parent::startOver();
    }

    /**
     * Send users invite.
     *
     * @return TrelloImporterIntegration
     */
    public function invite()
    {
        return $this->inviteUsers($this->mapping_table_name);
    }
}
