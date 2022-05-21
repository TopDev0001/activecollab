<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Authentication\Exception\InvalidPasswordException;
use ActiveCollab\Authentication\Password\Manager\PasswordManagerInterface;
use ActiveCollab\Foundation\History\Renderers\PasswordHistoryFieldRenderer;
use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use ActiveCollab\Module\OnDemand\Events\UserEvents\ChargeableUserEvents\ChargeableUserActivatedEvent;
use ActiveCollab\Module\OnDemand\Events\UserEvents\ChargeableUserEvents\ChargeableUserActivatedEventInterface;
use ActiveCollab\Module\OnDemand\Events\UserEvents\ChargeableUserEvents\ChargeableUserDeactivatedEvent;
use ActiveCollab\Module\OnDemand\Events\UserEvents\ChargeableUserEvents\ChargeableUserDeactivatedEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\UserEvents\UserInvitationAcceptedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\UserEvents\UserMovedToArchiveEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\UserEvents\UserMovedToTrashEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\UserEvents\UserRestoredFromArchiveEvent;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use ActiveCollab\Module\System\Utils\VisibleCompanyIdsResolver\VisibleCompanyIdsResolverInterface;
use ActiveCollab\Module\System\Utils\VisibleUserIdsResolver\VisibleUserIdsResolverInterface;
use ActiveCollab\Module\System\Wires\AvatarProxy;
use ActiveCollab\User\UserInterface\ImplementationUsingFirstAndLastName;
use Angie\Error;
use Angie\Search\SearchDocument\SearchDocumentInterface;

abstract class User extends BaseUser implements IUser, IHistory, IConfigContext
{
    use ImplementationUsingFirstAndLastName;

    /**
     * Extra permissions, for members.
     */
    const CAN_MANAGE_PROJECTS = 'can_manage_projects';
    const CAN_MANAGE_FINANCES = 'can_manage_finances';

    /**
     * Extra permissions, for clients.
     */
    const CAN_MANAGE_TASKS = 'can_manage_tasks';

    protected ?array $protect = [
        'password_reset_key',
        'password_reset_on',
    ];

    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'first_name',
                'last_name',
                'email',
                'password',
                'language_id',
                'company_id',
                'title',
                'phone',
                'im_type',
                'im_handle',
            ]
        );
    }

    public function getSearchFields(): array
    {
        return array_merge(
            parent::getSearchFields(),
            [
                'first_name',
                'last_name',
                'email',
            ]
        );
    }

    public function getBaseTypeName(bool $singular = true): string
    {
        return $singular ? 'user' : 'users';
    }

    public function getForcedFirstName(): string
    {
        $first_name = parent::getFirstName();

        if (empty($first_name)) {
            $email = $this->getEmail();

            return ucfirst_utf(
                substr_utf(
                    $email,
                    0,
                    strpos_utf($email, '@')
                )
            );
        }

        return $first_name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getDisplayName();
    }
    public function getDisplayName(bool $short = false): string
    {
        if ($short) {
            return AngieApplication::cache()->getByObject(
                $this,
                [
                    'short_display_name',
                ],
                function () {
                    return Users::getUserDisplayName(
                        [
                            'first_name' => $this->getFirstName(),
                            'last_name' => $this->getLastName(),
                            'email' => $this->getEmail(),
                        ],
                        true
                    );
                }
            );
        }

        return AngieApplication::cache()->getByObject(
            $this,
            [
                'display_name',
            ],
            function () {
                return Users::getUserDisplayName(
                    [
                        'first_name' => $this->getFirstName(),
                        'last_name' => $this->getLastName(),
                        'email' => $this->getEmail(),
                    ]
                );
            }
        );
    }

    /**
     * Return users language.
     *
     * @return Language
     */
    public function getLanguage()
    {
        $language = DataObjectPool::get(Language::class, $this->getLanguageId());

        return $language instanceof Language ? $language : Languages::findDefault();
    }

    /**
     * Return parent company.
     *
     * @return Company|DataObject|null
     */
    public function &getCompany()
    {
        return DataObjectPool::get(Company::class, $this->getCompanyId());
    }

    /**
     * Set user company.
     *
     * @return Company
     */
    public function setCompany(Company $company)
    {
        $company->addMembers([&$this]);

        return $company;
    }

    public function getVisibleCompanyIds(bool $use_cache = true): array
    {
        return AngieApplication::getContainer()
            ->get(VisibleCompanyIdsResolverInterface::class)
                ->getAll($this, $use_cache);
    }

    public function getVisibleCompanyUserIds(
        Company $company,
        int $min_state = STATE_VISIBLE,
        bool $use_cache = true
    ): array
    {
        return AngieApplication::getContainer()
            ->get(VisibleUserIdsResolverInterface::class)
                ->getInContext(
                    $this,
                    $company,
                    $min_state,
                    $use_cache
                );
    }

    /**
     * Return array of user ID-s that this user can see.
     *
     * @see TestUserVisibility::testGetVisibileUserIds()
     */
    public function getVisibleUserIds(
        int $min_state = STATE_VISIBLE,
        bool $use_cache = true
    ): array
    {
        if ($this->isNew()) {
            throw new LogicException('This method is available only for saved users.');
        }

        return AngieApplication::getContainer()
            ->get(VisibleUserIdsResolverInterface::class)
                ->getAll(
                    $this,
                    $min_state,
                    $use_cache
                );
    }

    public function getTeamIds(bool $use_cache = true): ?array
    {
        return AngieApplication::cache()->getByObject(
            $this,
            [
                'team_ids',
            ],
            function () {
                return DB::executeFirstColumn('SELECT `team_id` FROM `team_users` WHERE `user_id` = ?', $this->getId());
            },
            !$use_cache
        );
    }

    /**
     * Return ID-s of projects that this user is involved with.
     */
    public function getProjectIds(bool $use_cache = true): ?array
    {
        return AngieApplication::cache()->getByObject(
            $this,
            [
                'project_ids',
            ],
            function () {
                return DB::executeFirstColumn(
                    'SELECT id FROM projects AS p LEFT JOIN project_users AS u ON p.id = u.project_id WHERE u.user_id = ?',
                    $this->getId()
                );
            },
            !$use_cache
        );
    }

    /**
     * Return array of project that this user is involved with.
     *
     * @param  bool               $use_cache
     * @return DbResult|Project[]
     */
    public function getProjects($use_cache = true)
    {
        $project_ids = $this->getProjectIds($use_cache);

        if (!empty($project_ids)) {
            if ($projects = Projects::findByIds($project_ids)) {
                return $projects;
            }
        }

        return [];
    }

    /**
     * Return workspace (ActiveCollab instance) count.
     *
     * @return int
     */
    public function getWorkspaceCount()
    {
        return UserWorkspaces::getWorkspaceCountForUser($this);
    }

    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['language_id'] = $this->getLanguageId();
        $result['first_name'] = $this->getForcedFirstName();
        $result['last_name'] = $this->getLastName();
        $result['display_name'] = $this->getDisplayName();
        $result['short_display_name'] = $this->getDisplayName(true);
        $result['email'] = $this->getEmail();
        $result['is_email_at_example'] = strpos($this->getEmail(), '@example.com') !== false;
        $result['additional_email_addresses'] = $this->getAdditionalEmailAddresses();
        $result['daily_capacity'] = $this->getDailyCapacity();
        $result['is_pending_activation'] = $this->isPendingActivation();
        $result['avatar_url'] = $this->getAvatarUrl();

        if (empty($result['additional_email_addresses'])) {
            $result['additional_email_addresses'] = [];
        }

        $result['custom_permissions'] = $this->getSystemPermissions();

        $result['company_id'] = $this->getCompanyId();
        $result['title'] = $this->getTitle();
        $result['phone'] = $this->getPhone();
        $result['im_type'] = $this->getImType();
        $result['im_handle'] = $this->getImHandle();
        $result['workspace_count'] = $this->getWorkspaceCount();
        $result['first_login_on'] = $this->getFirstLoginOn();

        return $result;
    }

    /**
     * Return true if this instance is a client.
     *
     * @param  bool|false $explicit
     * @return bool
     */
    public function isClient($explicit = false)
    {
        return $explicit ? get_class($this) == Client::class : $this instanceof Client;
    }

    /**
     * Users by default can't use trash.
     *
     * @return bool
     */
    public function canUseTrash()
    {
        return false;
    }

    /**
     * Returns true if this user has access to reports section.
     *
     * @return bool
     */
    public function canUseReports()
    {
        return $this->isOwner() || $this->isManager();
    }

    // ---------------------------------------------------
    //  Feed tokens
    // ---------------------------------------------------

    /**
     * Return feed token.
     *
     * @return string
     */
    public function getFeedToken()
    {
        if ($this->isLoaded()) {
            $feed_token = $this->getAdditionalProperty('feed_token');

            if (empty($feed_token)) {
                $feed_token = make_string(80);

                $this->setAdditionalProperty('feed_token', $feed_token);
                $this->save();
            }

            return $this->getId() . '-' . $feed_token;
        } else {
            throw new NotImplementedError(__METHOD__, 'This mehtod is available only for saved objects');
        }
    }

    /**
     * Test reset feed token.
     */
    public function resetFeedToken()
    {
        if ($this->isLoaded()) {
            if ($this->getAdditionalProperty('feed_token')) {
                $this->setAdditionalProperty('feed_token', null);
                $this->save();
            }
        } else {
            throw new NotImplementedError(__METHOD__, 'This mehtod is available only for saved objects');
        }
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Return array of system permissions.
     *
     * @return array
     */
    public function getSystemPermissions()
    {
        $custom_permissions = $this->getAdditionalProperty('custom_permissions');

        if (empty($custom_permissions) || !is_array($custom_permissions)) {
            return [];
        }

        $result = [];

        foreach ($this->getAvailableCustomPermissions() as $permission) {
            if (in_array($permission, $custom_permissions)) {
                $result[] = $permission;
            }
        }

        return $result;
    }

    /**
     * Bulk set system permissions.
     *
     * @param array $value
     * @param bool  $force
     */
    public function setSystemPermissions($value, $force = false)
    {
        $custom_permissions = [];

        if ($value && is_foreachable($value)) {
            foreach ($value as $permission) {
                if ($force || $this->isCustomPermission($permission)) {
                    $custom_permissions[] = $permission;
                } else {
                    throw new InvalidParamError('permission', $permission, '$permission is not a custom permission in this role');
                }
            }
        }

        $this->setAdditionalProperty('custom_permissions', $custom_permissions);
    }

    /**
     * Return system permission value.
     *
     * @param  string $name
     * @return bool
     */
    public function getSystemPermission($name)
    {
        return in_array($name, $this->getSystemPermissions());
    }

    /**
     * Set system permission.
     *
     * @param string $name
     * @param bool   $value
     */
    public function setSystemPermission($name, $value)
    {
        $custom_permissions = $this->getAdditionalProperty('custom_permissions');

        if (empty($custom_permissions)) {
            $custom_permissions = [];
        }

        $value = (bool) $value;

        if ($this->isCustomPermission($name)) {
            if ($this->getSystemPermission($name) != $value) {
                if ($value) {
                    $custom_permissions[] = $name;
                } else {
                    foreach (array_keys($custom_permissions, $name) as $key) {
                        unset($custom_permissions[$key]);
                    }
                }
            }
        } else {
            throw new InvalidParamError('name', $name, "{$name} is not a custom permission in this role");
        }

        $this->setAdditionalProperty('custom_permissions', $custom_permissions);
    }

    public function getAvailableCustomPermissions(): array
    {
        return [];
    }

    /**
     * Return true if $name is a custom permission, and populate $value with permission value.
     *
     * @param  string $name
     * @return bool
     */
    public function isCustomPermission($name)
    {
        return in_array($name, $this->getAvailableCustomPermissions());
    }

    /**
     * Can user's profile can be changed by another user.
     *
     * @param  User $user
     * @return bool
     */
    public function canChangeUserProfile(self $user)
    {
        return (new UserProfilePermissionsChecker(
            $user,
            $this,
            AngieApplication::isOnDemand()
        ))->canChangeProfile();
    }

    /**
     * Can user's first and last name can be changed by another user.
     *
     * @param  User $user
     * @return bool
     */
    public function canChangeUserName(self $user)
    {
        return (new UserProfilePermissionsChecker(
            $user,
            $this,
            AngieApplication::isOnDemand()
        ))->canChangeName();
    }

    // ---------------------------------------------------
    //  Password and password policy
    // ---------------------------------------------------

    /**
     * Returns true if we have a valid password.
     *
     * @param  string $password
     * @return bool
     */
    public function isValidPassword($password)
    {
        $password_manager = AngieApplication::authentication()->getPasswordManager();

        if ($password_manager->verify($password, $this->getPassword(), $this->getPasswordHashedWith())) {
            // Hash using PHP if password is hashed with PBKDF2 or SHA1, or PHP with global salt.
            if ($this->needsRehash($password, $this->getPassword(), $this->getPasswordHashedWith())) {
                DB::execute(
                    'UPDATE `users` SET `password` = ?, `password_hashed_with` = ? WHERE `id` = ?',
                    $password_manager->hash($password, PasswordManagerInterface::HASHED_WITH_PHP),
                    PasswordManagerInterface::HASHED_WITH_PHP,
                    $this->getId()
                );

                AngieApplication::cache()->removeByObject($this);
            }

            return true;
        }

        return false;
    }

    private function needsRehash(string $password, string $hash, string $password_hashed_with): bool
    {
        if ($password_hashed_with === PasswordManagerInterface::HASHED_WITH_PHP
            && password_verify(APPLICATION_UNIQUE_KEY . $password, $hash)) {
            return true;
        }

        return AngieApplication::authentication()
            ->getPasswordManager()
            ->needsRehash(
                $hash,
                $password_hashed_with
            );
    }

    /**
     * Can user's password can be changed by another user.
     *
     * @param  User $user
     * @return bool
     */
    public function canChangeUserPassowrd(self $user)
    {
        return (new UserProfilePermissionsChecker(
            $user,
            $this,
            AngieApplication::isOnDemand()
        ))->canChangePassword();
    }

    /**
     * Change user password.
     *
     * Extracted to a separate method so it can be tested
     *
     * @param  User   $by
     * @param  string $by_password_verification
     * @param  string $new_password
     * @param  string $new_password_again
     * @param  bool   $save
     * @return $this
     */
    public function &changePassword(
        self $by,
        $by_password_verification,
        $new_password,
        $new_password_again,
        $save = true
    )
    {
        $errors = new ValidationErrors();

        if ($by->isValidPassword($by_password_verification)) {
            if ($new_password) {
                if ($new_password !== $new_password_again) {
                    $errors->addError('Password do not match', 'new_password');
                }

                try {
                    AngieApplication::authentication()->validatePasswordStrength($new_password);
                } catch (InvalidPasswordException $e) {
                    $errors->addError('password', $e->getMessage());
                }
            } else {
                $errors->addError('New password is required', 'new_password');
            }
        } else {
            $errors->addError('Your password is not valid', 'my_password');
        }

        if ($errors->hasErrors()) {
            throw $errors;
        }

        $this->setPassword($new_password);

        if ($save) {
            $this->save();
        }

        return $this;
    }

    /**
     * Force change user password.
     *
     * @param  User        $by
     * @param  string      $new_password
     * @param  string      $new_password_again
     * @param  string|null $old_password
     * @return $this
     */
    public function &forceChangePassword(
        self $by,
        $new_password,
        $new_password_again,
        $old_password = null,
        bool $validate_old_password_for_same_user = true,
        bool $save = true
    )
    {
        if ($validate_old_password_for_same_user && $this->getId() === $by->getId()) {
            if (empty($old_password)) {
                throw new Error('Old Password must be provided');
            }

            if (!$by->isValidPassword($old_password)) {
                throw new Error('Old password is not valid');
            }
        }

        AngieApplication::authentication()->validatePasswordStrength($new_password);

        if (AngieApplication::isOnDemand()) {
            AngieApplication::shepherdSyncer()->changeUserPassword(
                $this,
                $old_password,
                $new_password,
                $new_password_again
            );
        }

        $this->setPassword($new_password);

        if ($save) {
            $this->save();
        }

        return $this;
    }

    /**
     * Change user profile.
     *
     * @param  User $by
     * @param  bool $save
     * @return User
     */
    public function &changeProfile(self $by, array $data, $save = true)
    {
        if (empty($data['first_name']) || empty($data['last_name'])) {
            throw new Error('First and Last name must be provided');
        }

        if ($this->canChangeUserProfile($by)) {
            if (empty($data['email']) || empty($data['language_id'])) {
                throw new Error('Email and Language must be provided');
            }

            if (AngieApplication::isOnDemand() && $save) {
                AngieApplication::shepherdSyncer()->changeUserProfile($this, $data);
            }

            return Users::update($this, $data, $save);
        } elseif ($this->canChangeUserName($by)) {
            return Users::update($this, [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ], $save);
        } else {
            throw new Error('You can not change user profile data');
        }
    }

    /**
     * Raw password value before it is encoded.
     *
     * @var string
     */
    private $raw_password = false;

    public function setFieldValue(string $name, $value)
    {
        if ($name == 'password' && !$this->isLoading()) {
            $this->raw_password = (string) $value; // Remember raw password

            $value = AngieApplication::authentication()
                ->getPasswordManager()
                    ->hash($value, PasswordManagerInterface::HASHED_WITH_PHP);

            $this->setPasswordHashedWith(PasswordManagerInterface::HASHED_WITH_PHP);
        }

        return parent::setFieldValue($name, $value);
    }

    // ---------------------------------------------------
    //  Password recovery
    // ---------------------------------------------------

    /**
     * Begin password recovery.
     *
     * @return array
     */
    public function beginPasswordRecovery()
    {
        if (defined('SKELETON_KEY') && strlen(SKELETON_KEY) === 20) {
            if (AngieApplication::isInDevelopment()) {
                $key = SKELETON_KEY;
            } else {
                throw new Error('Skeleton key can be used only for development purposes');
            }
        } else {
            $key = make_string(20);
        }

        $this->setPasswordResetKey($key);
        $this->setPasswordResetOn(DateTimeValue::now());
        $this->save();

        AngieApplication::notifications()
            ->notifyAbout('system/password_recovery', $this)
            ->sendToUsers($this, true);

        return [
            'code_sent_to' => $this->getEmail(),
        ];
    }

    /**
     * Finish password recovery.
     *
     * @param  string $password
     * @return User
     */
    public function &finishPasswordRecovery($password)
    {
        try {
            DB::beginWork('Begin: user password recovery @ ' . __CLASS__);

            $this->setPassword($password);
            $this->setPasswordResetKey(null);
            $this->setPasswordResetOn(null);
            $this->save();

            UserSessions::terminateUserSessions($this);

            DB::commit('Done: user password recovery @ ' . __CLASS__);

            return $this;
        } catch (Exception $e) {
            DB::rollback('Rollback: user password recovery @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Return ture if password reset code is OK.
     *
     * @param  string $code
     * @return bool
     */
    public function validatePasswordRecoveryCode($code)
    {
        if ($code && strlen($code) === 20) {
            $reset_on = $this->getPasswordResetOn();

            return $this->getPasswordResetKey() == $code
                && $reset_on instanceof DateTimeValue
                && ($reset_on->getTimestamp() + 172800) > AngieApplication::currentTimestamp()->getCurrentTimestamp();
        }

        return false;
    }

    /**
     * Return reset password URL.
     *
     * @return string
     */
    public function getResetPasswordUrl()
    {
        if ($this->getPasswordResetKey() && $this->getPasswordResetOn() instanceof DateTimeValue) {
            return AngieApplication::getContainer()
                ->get(RouterInterface::class)
                    ->assemble(
                        'password_recovery_reset_password',
                        [
                            'user_id' => $this->getId(),
                            'timestamp' => $this->getPasswordResetOn()->getTimestamp(),
                            'code' => $this->getPasswordResetKey(),
                        ]
                    );
        } else {
            throw new Error('Recovery not initiated');
        }
    }

    private ?string $date_format = null;

    public function getDateFormat(): string
    {
        if ($this->date_format === null) {
            $this->date_format = ConfigOptions::getValueFor('format_date', $this);

            if ($this->date_format !== '%e. %b %Y') {
                $this->date_format = FORMAT_DATE;
            }
        }

        return $this->date_format;
    }

    private ?string $time_format = null;

    public function getTimeFormat(): string
    {
        if ($this->time_format === null) {
            $this->time_format = ConfigOptions::getValueFor('format_time', $this);

            if (empty($this->time_format)) {
                $this->time_format = FORMAT_TIME;
            }
        }

        return $this->time_format;
    }

    private ?string $date_time_format = null;

    public function getDateTimeFormat(): string
    {
        if ($this->date_time_format === null) {
            $this->date_time_format = $this->getDateFormat() . ' ' . $this->getTimeFormat();
        }

        return $this->date_time_format;
    }

    public function getHistoryFieldRenderers(): array
    {
        $renderers = parent::getHistoryFieldRenderers();

        $renderers['password'] = new PasswordHistoryFieldRenderer();

        return $renderers;
    }

    public function canSeeFieldHistory(IHistory $parent, string $field_name): bool
    {
        return true;
    }

    // ---------------------------------------------------
    //  Interface implementation
    // ---------------------------------------------------

    public function getUsername()
    {
        return $this->getEmail();
    }

    public function canAuthenticate()
    {
        return $this->isActive();
    }

    public function getRoutingContext(): string
    {
        return 'user';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'user_id' => $this->getId(),
        ];
    }

    public function getUrlPath(): string
    {
        return '/users/' . $this->getId();
    }

    public function getSearchDocument(): SearchDocumentInterface
    {
        return new UserSearchDocument($this);
    }

    // ---------------------------------------------------
    //  Avatars
    // ---------------------------------------------------

    public function clearAvatar()
    {
        if ($this->getAvatarLocation()) {
            AngieApplication::storage()->deleteFile($this->getAvatarType(), $this->getAvatarLocation());

            $this->setAvatarLocation('');
            $this->save();
        }
    }

    /**
     * @param string $md5_hash
     */
    public function setAvatarMd5($md5_hash)
    {
        $this->setAdditionalProperty('avatar_md5', $md5_hash);
    }

    /**
     * @return string
     */
    public function getAvatarMd5()
    {
        return $this->getAdditionalProperty('avatar_md5');
    }

    /**
     * @return string
     */
    public function getAvatarType()
    {
        if (AngieApplication::isInProduction() && !AngieApplication::isOnDemand()) {
            return LocalFile::class;
        }

        return !empty($this->getAvatarMd5()) ? WarehouseFile::class : LocalFile::class;
    }

    /**
     * Return user avatar URL.
     *
     * @param  string|int $size
     * @return string
     */
    public function getAvatarUrl($size = '--SIZE--')
    {
        return AngieApplication::getProxyUrl(
            AvatarProxy::class,
            EnvironmentFramework::INJECT_INTO,
            [
                'user_id' => $this->getId(),
                'size' => $size,
                'timestamp' => $this->getUpdatedOn()->getTimestamp(),
            ]
        );
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Returns true if this particular account is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return !($this->getIsArchived() || $this->getIsTrashed());
    }

    /**
     * Return true if this instance is a member.
     *
     * @param  bool $explicit
     * @return bool
     */
    public function isMember($explicit = false)
    {
        return $explicit ?
            get_class($this) == Member::class : // Strictly check for Member class
            $this instanceof Member;            // Return true in case of all classes that extend member
    }

    /**
     * Returns true only if this person is owner of this application.
     *
     * @return bool
     */
    public function isOwner()
    {
        return $this instanceof Owner;
    }

    /**
     * Returns true if this user is manager.
     *
     * @return bool
     */
    public function isManager()
    {
        return $this->getSystemPermission(self::CAN_MANAGE_PROJECTS) || $this->getSystemPermission(self::CAN_MANAGE_FINANCES);
    }

    /**
     * Return true if this user is subcontrator (member in a non-owner company).
     */
    public function isSubcontractor(): bool
    {
        return $this->isMember(true)
            && $this->getCompanyId() != AngieApplication::getContainer()
                ->get(OwnerCompanyResolverInterface::class)
                    ->getId();
    }

    /**
     * Returns true if this user has final management permissions.
     *
     * @param  bool $explicit
     * @return bool
     */
    public function isFinancialManager($explicit = false)
    {
        if ($explicit) {
            return $this->isMember(true) && $this->getSystemPermission(self::CAN_MANAGE_FINANCES);
        } else {
            return $this->isOwner() || ($this->isMember(true) && $this->getSystemPermission(self::CAN_MANAGE_FINANCES));
        }
    }

    public function canView(User $user): bool
    {
        if ($user->getId() == $this->getId()) {
            return true; // Can see self
        }

        $visible_user_ids = $user->getVisibleUserIds(STATE_TRASHED);

        if (empty($visible_user_ids) || !in_array($this->getId(), $visible_user_ids)) {
            return false;
        }

        return $this->getIsTrashed() ? $user->canUseTrash() : true;
    }

    public function canEdit(User $user): bool
    {
        return $user->is($this) || $user->isOwner() || $this->isCreatedBy($user) || ($user->isPowerUser() && !$this->isOwner());
    }

    public function canArchive(User $user): bool
    {
        return $this->canDelete($user);
    }

    public function canTrash(User $user): bool
    {
        return $this->canDelete($user);
    }

    /**
     * Return true if $user can delete this user account.
     */
    public function canDelete(User $user): bool
    {
        if ($user->is($this) || Users::isLastOwner($this)) {
            return false; // Can't delete self or last owner
        }

        if ($user->isPowerUser()) {
            return $this->isOwner() ? $user->isOwner() : true; // Project manager can delete everyone except owner
        }

        return false;
    }

    /**
     * Returns true if this user has permissions to see private objects.
     *
     * @return bool
     */
    public function canSeePrivate()
    {
        return $this->isMember();
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Returns true if this user has global project management permissions.
     *
     * @param  bool $explicit
     * @return bool
     */
    public function isPowerUser($explicit = false)
    {
        if (!$explicit && $this->isOwner()) {
            return true;
        }

        return $this->isMember(true) && $this->getSystemPermission(self::CAN_MANAGE_PROJECTS);
    }

    /**
     * Returns true if this user has extra task management permissions.
     *
     * @param  bool|false $explicit
     * @return bool
     */
    public function isPowerClient($explicit = false)
    {
        if (!$explicit && $this->isOwner()) {
            return true;
        }

        return $this->isClient(true) && $this->getSystemPermission(self::CAN_MANAGE_TASKS);
    }

    /**
     * Returns true if $user can change password of this user.
     *
     * @param  User $user
     * @return bool
     */
    public function canChangePassword(self $user)
    {
        return $this->canEdit($user);
    }

    /**
     * Returns true if $user can change this users role.
     *
     * @param  User       $user
     * @param  array|bool $with_custom_permissions
     * @return bool
     */
    public function canChangeRole(self $user, $with_custom_permissions = false)
    {
        return ($this->getId() !== $user->getId()) && ($user->isOwner() || ($user->isPowerUser(true) && !$this->isPowerUser()));
    }

    /**
     * Returns true if $user can change this user daily capacity.
     *
     * @return bool
     */
    public function canChangeDailyCapacity(self $user)
    {
        if ($user->getId() === $this->getId()) {
            return true;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->isFinancialManager()) {
            return true;
        }

        if ($user->isPowerUser()) {
            $visible_user_ids = $user->getVisibleUserIds();

            if (empty($visible_user_ids) || !in_array($this->getId(), $visible_user_ids)) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function canAddAvailabilityRecord(self $user)
    {
        return $this->canChangeDailyCapacity($user); // for now, logic is same as for daily capacity
    }

    public function canManageTasks(): bool
    {
        return $this->isOwner()
            || $this->isMember()
            || ($this->isClient(true) && $this->getSystemPermission(static::CAN_MANAGE_TASKS));
    }

    /**
     * Return true if $user can manage API subscriptions for this user.
     *
     * @param  User $user
     * @return bool
     */
    public function canManageApiSubscriptions(self $user)
    {
        return $user->isOwner() || $user->getId() === $this->getId();
    }

    // ---------------------------------------------------
    //  Email
    // ---------------------------------------------------

    /**
     * Return array of additional email addresses.
     *
     * @return array|null
     */
    public function getAdditionalEmailAddresses()
    {
        return Users::getAdditionalEmailAddressesByUser($this);
    }

    /**
     * Set additional email addresses.
     *
     * @param array|null $addresses
     */
    public function setAdditionalEmailAddresses($addresses)
    {
        try {
            DB::beginWork('Set additional addresses @ ' . __CLASS__);

            DB::execute('DELETE FROM user_addresses WHERE user_id = ?', $this->getId());

            if ($addresses && is_foreachable($addresses)) {
                $to_add = [];

                $primary_email_address = strtolower($this->getEmail());

                foreach ($addresses as $address) {
                    $validate_address = strtolower(trim($address));

                    if (empty($validate_address) || $validate_address == $primary_email_address || in_array($validate_address, $to_add)) {
                        continue;
                    }

                    if (!is_valid_email($validate_address)) {
                        throw new InvalidParamError('to_add', $validate_address, 'Invalid email address');
                    }

                    if (Users::isEmailAddressInUse($validate_address, $this)) {
                        throw new InvalidParamError('to_add', $validate_address, 'Email address in use');
                    }

                    $to_add[] = $validate_address;
                }

                if (count($to_add)) {
                    $batch = new DBBatchInsert('user_addresses', ['user_id', 'email']);

                    foreach ($to_add as $address) {
                        $batch->insert($this->getId(), $address);
                    }

                    $batch->done();
                }
            }

            DB::commit('Additional addresses set @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to set additional addresses @ ' . __CLASS__);

            throw $e;
        }
    }

    // ---------------------------------------------------
    //  Invitations
    // ---------------------------------------------------

    /**
     * Invite this user (and return invitation code).
     *
     * When we try to invite user who already logged in, system will raise an exception
     *
     * @param  null                                    $to
     * @param  bool                                    $resend_if_already_invited
     * @return DataObject|DbResult|UserInvitation|null
     */
    public function invite(self $by, $to = null, $resend_if_already_invited = false)
    {
        if ($this->canBeInvited()) {
            $invitation = UserInvitations::findFor($this);

            $send_email = false;

            if (empty($invitation)) {
                /** @var UserInvitation $invitation */
                $invitation = UserInvitations::create(
                    [
                        'user_id' => $this->getId(),
                        'code' => make_string(20),
                        'created_by_id' => $by->getId(),
                    ],
                    false
                );

                if ($to instanceof DataObject) {
                    $invitation->setInvitedTo($to);
                }

                $invitation->save();

                $send_email = true;

                AngieApplication::cache()->setByObject($this, 'invitation_id', $invitation->getId());
            } else {
                if ($resend_if_already_invited) {
                    Users::update(
                        $this,
                        [
                            'created_by_id' => $by->getId(),
                            'created_by_name' => $by->getDisplayName(),
                            'created_by_email' => $by->getEmail(),
                            'created_on' => new DateTimeValue(),
                        ]
                    );
                    $invitation = UserInvitations::update(
                        $invitation,
                        [
                            'created_by_id' => $by->getId(),
                            'created_by_name' => $by->getDisplayName(),
                            'created_by_email' => $by->getEmail(),
                            'created_on' => new DateTimeValue(),
                        ]
                    );
                    $send_email = true;
                }
            }

            if (AngieApplication::isOnDemand()) {
                AngieApplication::shepherdSyncer()->addAccountUser($this);
            }

            if ($send_email) {
                /** @var InvitationNotification $notification */
                $notification = AngieApplication::notifications()->notifyAbout('system/invitation', $this, $by);
                $notification->setInvitation($invitation);
                $notification->setInvitedTo($to);
                $notification->sendToUsers($this, true);
            }

            return $invitation;
        } else {
            throw new NotImplementedError(__METHOD__, "Can't invite user who already logged in");
        }
    }

    /**
     * Get invitation for this user.
     *
     * @return UserInvitation
     */
    public function getInvitation()
    {
        return UserInvitations::findFor($this);
    }

    /**
     * Record that user accepted the invitation.
     */
    public function invitationAccepted()
    {
        $invitation = UserInvitations::findFor($this);

        if (!$invitation) {
            return;
        }

        DataObjectPool::announce(new UserInvitationAcceptedEvent($this));

        $invitation->delete();
    }

    public function isChargeable(): bool
    {
        return ($this->isPowerClient() || !$this->isClient())
            && $this->getLastLoginOn() instanceof DateTimeValue
            && !$this->isExampleUser()
            && $this->isActive();
    }

    public function isExampleUser(): bool
    {
        return Users::isExampleEmail($this->getEmail());
    }

    public function canBeInvited(): bool
    {
        return $this->getLastLoginOn() === null;
    }

    public function isPendingActivation(): bool
    {
        return $this->getLastLoginOn() === null && (bool) UserInvitations::getInvitationIdForUser($this);
    }

    /**
     * Return timestamp when this user last logged in.
     *
     * @return DateTimeValue|null
     */
    public function getLastLoginOn()
    {
        return Users::getLastLoginOnForUser($this);
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Return true if this user can be restored from Archive or Trash.
     *
     * @return bool
     */
    private function canBeRestored()
    {
        return !(AngieApplication::isOnDemand() && !OnDemand::canAddUsersBasedOnCurrentPlan(get_class($this), $this->getSystemPermissions(), 1, [$this->getEmail()]));
    }

    public function moveToArchive(User $by, $bulk = false)
    {
        try {
            DB::beginWork('Begin: move user to archive @ ' . __CLASS__);

            foreach ($this->getProjects() as $project) {
                $project->touch();
            }

            UserSessions::terminateUserSessions($this);

            // IAssignments
            Tasks::revokeAssignee($this, $by);
            Subtasks::revokeAssignee($this, $by);
            RecurringTasks::revokeAssignee($this, $by);
            ProjectTemplateElements::revokeAssignee($this->getId());

            // Projects, project templates and teams
            Projects::revokeMember($this, $by);
            ProjectTemplates::revokeMember($this, $by);
            Teams::revokeMember($this, $by);

            $was_chargeable = $this->isChargeable();

            parent::moveToArchive($by, $bulk);

            if (AngieApplication::isOnDemand()) {
                if (!$this->isChargeable() && $was_chargeable) {
                    AngieApplication::eventsDispatcher()->trigger(
                        new ChargeableUserDeactivatedEvent(
                            $this,
                            ChargeableUserDeactivatedEventInterface::USER_DEACTIVATED_REASON
                        )
                    );
                }

                AngieApplication::shepherdSyncer()->syncUserStatus($this);
            }

            if (!$bulk) {
                DataObjectPool::announce(new UserMovedToArchiveEvent($this));
            }

            DB::commit('Done: move user to archive @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: move user to archive @ ' . __CLASS__);

            throw $e;
        }
    }

    public function moveToTrash(self $by = null, $bulk = false)
    {
        try {
            DB::beginWork('Begin: move user to trash @ ' . __CLASS__);

            foreach ($this->getProjects() as $project) {
                $project->touch();
            }

            UserSessions::terminateUserSessions($this);
            Teams::revokeMemberWithoutCheck($this);

            if (!$bulk && $by instanceof User) {
                // IAssignments
                Tasks::revokeAssignee($this, $by);
                Subtasks::revokeAssignee($this, $by);
                RecurringTasks::revokeAssignee($this, $by);
                ProjectTemplateElements::revokeAssignee($this->getId());

                //Project, project templates
                Projects::revokeMember($this, $by);
                ProjectTemplates::revokeMember($this, $by);
            }

            $was_chargeable = $this->isChargeable();

            parent::moveToTrash($by, $bulk);

            if (AngieApplication::isOnDemand()) {
                if (!$this->isChargeable() && $was_chargeable) {
                    AngieApplication::eventsDispatcher()->trigger(
                        new ChargeableUserDeactivatedEvent(
                            $this,
                            ChargeableUserDeactivatedEventInterface::USER_DEACTIVATED_REASON
                        )
                    );
                }

                AngieApplication::shepherdSyncer()->syncUserStatus($this);
            }

            if (!$bulk) {
                DataObjectPool::announce(new UserMovedToTrashEvent($this));
            }

            DB::commit('Done: move user to trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: move user to trash @ ' . __CLASS__);

            throw $e;
        }
    }

    public function restoreFromTrash($bulk = false)
    {
        if (!$this->canBeRestored()) {
            throw new Error("Can't restore user from trash, check your plan restriction.");
        }

        parent::restoreFromTrash($bulk);

        if (AngieApplication::isOnDemand()) {
            if ($this->isChargeable()) {
                AngieApplication::eventsDispatcher()->trigger(
                    new ChargeableUserActivatedEvent(
                        $this,
                        ChargeableUserActivatedEventInterface::USER_REACTIVATED_REASON
                    )
                );
            }

            AngieApplication::shepherdSyncer()->syncUserStatus($this);
        }
    }

    public function restoreFromArchive($bulk = false)
    {
        if (!$this->canBeRestored()) {
            throw new Error("Can't restore user from archive, check your plan restriction.");
        }

        parent::restoreFromArchive($bulk);

        if (AngieApplication::isOnDemand()) {
            if ($this->isChargeable()) {
                AngieApplication::eventsDispatcher()->trigger(
                    new ChargeableUserActivatedEvent(
                        $this,
                        ChargeableUserActivatedEventInterface::USER_REACTIVATED_REASON
                    )
                );
            }

            AngieApplication::shepherdSyncer()->syncUserStatus($this);
        }
        if (!$bulk) {
            DataObjectPool::announce(new UserRestoredFromArchiveEvent($this));
        }
    }

    public function validate(ValidationErrors &$errors)
    {
        $name_components = [
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
        ];

        foreach ($name_components as $field_name => $field_value) {
            if (empty($field_value)) {
                continue;
            }

            $field_value = mb_strtolower($field_value);

            foreach (['www.', '://', '.ru'] as $bit_to_check) {
                if (strpos($field_value, $bit_to_check) !== false) {
                    $errors->addError('Name is not valid', $field_name);

                    break;
                }
            }
        }

        if (!empty($this->getFirstName()) && strpos($this->getFirstName(), '://') !== false) {
            $errors->addError(lang('First name is invalid!'));
        }

        if (!empty($this->getLastName()) && strpos($this->getLastName(), '://') !== false) {
            $errors->addError(lang('Last name is invalid!'));
        }

        if ($this->validatePresenceOf('email', 5)) {
            if (is_valid_email($this->getEmail())) {
                if ($this->isNew()) {
                    $in_use = Users::isEmailAddressInUse($this->getEmail());
                } else {
                    $in_use = Users::isEmailAddressInUse($this->getEmail(), $this->getId());
                }

                if ($in_use) {
                    $errors->fieldValueNeedsToBeUnique('email');
                }
            } else {
                $errors->addError('Email value is not valid', 'email');
            }
        } else {
            $errors->fieldValueIsRequired('email');
        }

        if ($this->isNew() || $this->raw_password !== false) {
            try {
                AngieApplication::authentication()->validatePasswordStrength($this->raw_password);
            } catch (InvalidPasswordException $e) {
                $errors->addError($e->getMessage(), 'password');
            }
        }

        if (!$this->validatePresenceOf('type')) {
            $errors->fieldValueIsRequired('type');
        }

        if (!$this->validatePresenceOf('language_id')) {
            $errors->fieldValueIsRequired('language');
        }

        if ($this->validatePresenceOf('daily_capacity') &&
            !$this->validateValueInRange('daily_capacity', 1, 24)
        ) {
            $errors->addError(lang('User daily capacity can be from 1 to 24'), 'daily_capacity');
        }
    }

    public function save()
    {
        $modified_fields = $this->getModifiedFields();

        $is_new = $this->isNew();
        $name_changed = $this->isLoaded() && (in_array('first_name', $modified_fields) || in_array('last_name', $modified_fields) || in_array('email', $modified_fields));
        $state_changed = in_array('is_archived', $modified_fields) || in_array('is_trashed', $modified_fields);

        if (!$this->getLanguageId()) {
            $this->setLanguageId(1);
        }

        parent::save();

        if (AngieApplication::isOnDemand() && ($is_new || $state_changed)) {
            AngieApplication::shepherdSyncer()->addAccountUser($this);
        }

        // clear cache for general conversation if active members from owner company has been changed
        if ($state_changed && $this->isMember() && $this->isEmployee() && $general = Conversations::getGeneralConversation()) {
            Conversations::clearCacheFor($general->getId());
        }

        // ---------------------------------------------------
        //  Clear cache if type changed. Also, make sure that
        //  display name caches are cleared when first name,
        //  last name or email address are updated
        // ---------------------------------------------------

        if (in_array('type', $modified_fields)) {
            AngieApplication::cache()->clearModelCache();
        } else {
            if ($name_changed) {
                AngieApplication::cache()->removeByObject($this, 'display_name');
                AngieApplication::cache()->removeByObject($this, 'short_display_name');
            }
        }
    }

    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Deleting user @ ' . __CLASS__);

            $escaped_user_type = DB::escape(get_class($this));
            $escaped_user_id = DB::escape($this->getId());
            $escaped_user_name = DB::escape($this->getDisplayName());
            $escaped_user_email = DB::escape($this->getEmail());

            // ---------------------------------------------------
            //  Update task and subtask assignments
            // ---------------------------------------------------

            if ($task_ids = DB::executeFirstColumn("SELECT id FROM tasks WHERE assignee_id = $escaped_user_id")) {
                DB::execute('UPDATE tasks SET assignee_id = 0 WHERE id IN (?)', $task_ids);
            }
            DB::execute("UPDATE subtasks SET assignee_id = 0 WHERE assignee_id = $escaped_user_id");

            // ---------------------------------------------------
            //  Update recurring task assignments
            // ---------------------------------------------------

            if ($recurring_task_ids = DB::executeFirstColumn("SELECT id FROM recurring_tasks WHERE assignee_id = $escaped_user_id")) {
                DB::execute('UPDATE recurring_tasks SET assignee_id = 0 WHERE id IN (?)', $recurring_task_ids);
            }

            // ---------------------------------------------------
            //  Update time records and expenses
            // ---------------------------------------------------

            DB::execute("UPDATE time_records SET user_id = 0, user_name = $escaped_user_name, user_email = $escaped_user_email WHERE user_id = $escaped_user_id");
            DB::execute("UPDATE expenses SET user_id = 0, user_name = $escaped_user_name, user_email = $escaped_user_email WHERE user_id = $escaped_user_id");

            // ---------------------------------------------------
            //  Update project users
            // ---------------------------------------------------

            DB::execute("UPDATE projects SET leader_id = ? WHERE leader_id = $escaped_user_id", 0);
            DB::execute("DELETE FROM project_users WHERE user_id = $escaped_user_id"); // Drop project users relations

            if (AngieApplication::isOnDemand()) {
                AngieApplication::shepherdSyncer()->revokeAccess($this);
            }

            // ---------------------------------------------------
            //  System clean-up
            // ---------------------------------------------------

            ConfigOptions::removeValuesFor($this);

            if (AngieApplication::isFrameworkLoaded('reminders')) {
                Reminders::deleteByUser($this);
            }

            if ($this->getAvatarLocation()) {
                AngieApplication::storage()->deleteFile($this->getAvatarType(), $this->getAvatarLocation());
            }

            // ---------------------------------------------------
            //  Clean up access logs
            // ---------------------------------------------------

            DB::execute("DELETE FROM access_logs WHERE accessed_by_id IN ($escaped_user_id)");

            // ---------------------------------------------------
            //  Clean up notifications
            // ---------------------------------------------------

            // Drop notifications about users that are being removed
            $notification_ids = DB::executeFirstColumn(
                "SELECT id FROM notifications WHERE parent_type = $escaped_user_type AND parent_id = $escaped_user_id"
            );

            if ($notification_ids) {
                NotificationRecipients::deleteBy($notification_ids);
                DB::execute('DELETE FROM notifications WHERE id IN (?)', $notification_ids);
            }

            // Drop notifications where deleted user is recipient
            NotificationRecipients::deleteBy([], [$escaped_user_id]);

            // Update notifications where deleted users are senders
            DB::execute("UPDATE notifications SET sender_id = NULL, sender_name = $escaped_user_name, sender_email = $escaped_user_email WHERE sender_id = $escaped_user_id");

            // Clean up the rest of the data
            $this->dropUserRelations($escaped_user_id);
            $this->discoverAndUpdateTables($escaped_user_id, $escaped_user_name, $escaped_user_email);

            parent::delete($bulk);

            DB::commit('User deleted @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to delete user @ ' . __CLASS__);

            throw $e;
        }

        AngieApplication::cache()->clear();
    }

    /**
     * Drop user relations.
     *
     * @param string $escaped_user_id
     */
    private function dropUserRelations($escaped_user_id)
    {
        foreach (['api_csubscriptions', 'calendar_users', 'favorites', 'reminder_users', 'security_logs', 'subscriptions', 'user_addresses', 'user_invitations', 'user_sessions'] as $table) {
            if (DB::tableExists($table)) {
                DB::execute("DELETE FROM $table WHERE user_id = $escaped_user_id");
            }
        }
    }

    /**
     * Discover and update tables.
     *
     * @param string $escaped_user_id
     * @param string $escaped_user_name
     * @param string $escaped_user_email
     */
    private function discoverAndUpdateTables($escaped_user_id, $escaped_user_name, $escaped_user_email)
    {
        $update_parents = $update_by_ids = $update_fieldset = [];

        $escaped_user_types = DB::escape(Users::getAvailableUserClasses());

        foreach (DB::listTables() as $table) {
            $table_fields = DB::listTableFields($table);

            foreach ($table_fields as $field) {
                if ($field == 'parent_type' && in_array('parent_id', $table_fields)) {
                    if (DB::executeFirstCell("SELECT COUNT(*) AS 'row_count' FROM $table WHERE parent_type IN ($escaped_user_types) AND parent_id = $escaped_user_id") > 0) {
                        $update_parents[] = $table;
                    }
                } elseif (str_ends_with($field, '_by_id')) {
                    $name = substr($field, 0, strlen($field) - 6);

                    if (in_array("{$name}_by_name", $table_fields) && in_array("{$name}_by_email", $table_fields)) {
                        if (empty($update_fieldset[$table])) {
                            $update_fieldset[$table] = [];
                        }

                        $update_fieldset[$table][] = $name;
                    } else {
                        if (empty($update_by_ids[$table])) {
                            $update_by_ids[$table] = [];
                        }

                        $update_by_ids[$table][] = $field;
                    }
                }
            }
        }

        foreach ($update_by_ids as $table => $fields) {
            foreach ($fields as $field) {
                try {
                    DB::execute("UPDATE $table SET $field = NULL WHERE $field = $escaped_user_id"); // In case we can have NULL, set NULL
                } catch (DBQueryError $e) {
                    DB::execute("UPDATE $table SET $field = '0' WHERE $field = $escaped_user_id"); // On error, set 0
                }
            }
        }

        foreach ($update_fieldset as $table => $fields) {
            foreach ($fields as $field) {
                try {
                    DB::execute("UPDATE $table SET {$field}_by_id = NULL, {$field}_by_name = $escaped_user_name, {$field}_by_email = $escaped_user_email WHERE {$field}_by_id = $escaped_user_id"); // In case we can have NULL, set NULL
                } catch (DBQueryError $e) {
                    DB::execute("UPDATE $table SET {$field}_by_id = '0', {$field}_by_name = $escaped_user_name, {$field}_by_email = $escaped_user_email WHERE {$field}_by_id = $escaped_user_id"); // In case we can't set NULL, set 0
                }
            }
        }
    }

    protected function getSearchEngine()
    {
        return AngieApplication::search();
    }

    public function isPaid()
    {
        return !empty($this->getPaidOn());
    }

    public function isPrivacyVersionUpdated()
    {
        return $this->getPolicyVersion() == AngieApplication::getCurrentPolicyVersion();
    }
}
