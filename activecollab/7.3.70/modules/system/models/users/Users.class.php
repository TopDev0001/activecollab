<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\OnDemand\Events\UserEvents\ChargeableUserEvents\ChargeableUserActivatedEvent;
use ActiveCollab\Module\OnDemand\Events\UserEvents\ChargeableUserEvents\ChargeableUserActivatedEventInterface;
use ActiveCollab\Module\OnDemand\Events\UserEvents\ChargeableUserEvents\ChargeableUserDeactivatedEvent;
use ActiveCollab\Module\OnDemand\Events\UserEvents\ChargeableUserEvents\ChargeableUserDeactivatedEventInterface;
use ActiveCollab\Module\System\Collections\ActivityLog\RangeUserActivityLogsByCursorCollection;
use ActiveCollab\Module\System\Collections\ActivityLog\RangeUserActivityLogsForCursorCollection;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\UserEvents\UserCreatedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\UserEvents\UserUpdatedEvent;
use ActiveCollab\Module\System\Model\Conversation\GroupConversation;
use Angie\Authentication\Exception\ResetPasswordException as ResetPasswordError;
use Angie\Error;
use Angie\NamedList;

class Users extends BaseUsers
{
    /**
     * Return new collection.
     *
     * @param  User|null                                                                                                                               $user
     * @return InitialSettingsCollection|InitialUserSettingsCollection|ModelCollection|OpenAssignmentsForAssigneeCollection|UserActivityLogsCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'initial')) {
            return self::prepareInitialCollection($collection_name, $user);
        } elseif (str_starts_with($collection_name, 'open_assignments_for_assignee')) {
            return self::prepareOpenAssignmentsForAssigneeCollection($collection_name, $user);
        } else {
            if ($user instanceof User) {
                if (str_starts_with($collection_name, 'notifications_for_recipient') || str_starts_with($collection_name, 'unread_notifications_for_recipient')) {
                    return self::prepareNotificationsForCollection($collection_name, $user);
                } elseif (str_starts_with($collection_name, 'object_updates_for_recipient')) {
                    return self::prepareObjectUpdatesForCollection($collection_name, $user);
                } elseif (str_starts_with($collection_name, 'recent_object_updates_for_recipient')) {
                    return self::prepareRecentObjectUpdatesForCollection($collection_name, $user);
                } elseif (str_starts_with($collection_name, 'activity_logs_for')) {
                    return self::prepareActivityLogsForCollection($collection_name, $user);
                }  elseif (str_starts_with($collection_name, 'range_activity_logs_for')) {
                    return self::prepareRangeActivityLogsForCollection($collection_name, $user);
                } elseif (
                    str_starts_with($collection_name, 'cursor_range_activity_logs_by') ||
                    str_starts_with($collection_name, 'cursor_range_activity_logs_for')
                ) {
                    return self::prepareRangeActivityLogsForOrByCursorCollection($collection_name, $user);
                } elseif (str_starts_with($collection_name, 'daily_activity_logs_for')) {
                    return self::prepareDailyActivityLogsForCollection($collection_name, $user);
                } elseif (str_starts_with($collection_name, 'activity_logs_by')) {
                    return self::prepareActivityLogsByCollection($collection_name, $user);
                }  elseif (str_starts_with($collection_name, 'range_activity_logs_by')) {
                    return self::prepareRangeActivityLogsByCollection($collection_name, $user);
                } else {
                    return self::prepareUsersCollection($collection_name, $user);
                }
            } else {
                throw new InvalidParamError('user', $user, '$user is required to be a user');
            }
        }
    }

    /**
     * Prepare initial settings and data collection.
     *
     * @param  string                                                  $collection_name
     * @param  User|null                                               $user
     * @return InitialSettingsCollection|InitialUserSettingsCollection
     */
    public static function prepareInitialCollection($collection_name, $user)
    {
        if ($collection_name === 'initial') {
            $collection = new InitialSettingsCollection($collection_name);
        } elseif ($collection_name === 'initial_for_logged_user') {
            $collection = new InitialUserSettingsCollection($collection_name);
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        if ($user instanceof User) {
            $collection->setWhosAsking($user);
        }

        return $collection;
    }

    /**
     * Prepare open assignments for assignee collection.
     *
     * @param  string                               $collection_name
     * @param  User                                 $user
     * @return OpenAssignmentsForAssigneeCollection
     */
    public static function prepareOpenAssignmentsForAssigneeCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);
        $assignee_id = array_pop($bits);

        $assignee = DataObjectPool::get(User::class, $assignee_id);

        if (!$assignee instanceof User) {
            throw new ImpossibleCollectionError("Assignee #{$assignee_id} not found");
        }

        if (!$assignee->isPowerClient(true) && !$assignee->isMember()) {
            throw new ImpossibleCollectionError("Assignee #{$assignee_id} not a Member");
        }

        $collection = new OpenAssignmentsForAssigneeCollection($collection_name);
        $collection->setAssignee($assignee);
        $collection->setWhosAsking($user);

        return $collection;
    }

    /**
     * Prepare notifications collection.
     *
     * @param  string                      $collection_name
     * @param  User|null                   $user
     * @return UserNotificationsCollection
     */
    private static function prepareNotificationsForCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);
        $recipient = DataObjectPool::get('User', array_pop($bits));

        if ($recipient instanceof User && $recipient->isActive()) {
            return (new UserNotificationsCollection($collection_name))
                ->setWhosAsking($user)
                ->setRecipient($recipient);
        } else {
            throw new ImpossibleCollectionError('User not found or not active');
        }
    }

    /**
     * Prepare notifications collection.
     *
     * @param  string                      $collection_name
     * @param  User|null                   $user
     * @return UserObjectUpdatesCollection
     */
    private static function prepareObjectUpdatesForCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        $page = array_pop($bits);
        array_pop($bits); // _page_

        $recipient = DataObjectPool::get('User', array_pop($bits));

        if ($recipient instanceof User && $recipient->isActive()) {
            $collection = new UserObjectUpdatesCollection($collection_name);
            $collection->fetchTotalNumberOfUnreadObjects(true);

            $collection->setPagination($page, 30);
            $collection->setWhosAsking($user);
            $collection->setRecipient($recipient);

            return $collection;
        } else {
            throw new ImpossibleCollectionError('User not found or not active');
        }
    }

    /**
     * Prepare recent object updates for recipient collection.
     *
     * @param  string                      $collection_name
     * @param  User|null                   $user
     * @return UserObjectUpdatesCollection
     */
    private static function prepareRecentObjectUpdatesForCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);
        $recipient = DataObjectPool::get(User::class, array_pop($bits));

        if ($recipient instanceof User && $recipient->isActive()) {
            $collection = new UserObjectUpdatesCollection($collection_name);

            $collection->setPagination(1, 30);
            $collection->setWhosAsking($user);
            $collection->setRecipient($recipient);
            $collection->fetchTotalNumberOfUnreadObjects(true);

            return $collection;
        } else {
            throw new ImpossibleCollectionError('User not found or not active');
        }
    }

    /**
     * Prepare activity logs for collection.
     *
     * @param  string                     $collection_name
     * @param  User|null                  $user
     * @return UserActivityLogsCollection
     */
    private static function prepareActivityLogsForCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        $page = array_pop($bits); // Remove page #
        array_pop($bits); // Remove _page_

        $for = DataObjectPool::get('User', array_pop($bits));

        if ($for instanceof User && $for->isActive()) {
            $collection = new UserActivityLogsForCollection($collection_name);

            $collection->setWhosAsking($user);
            $collection->setForOrBy($for);
            $collection->setPagination($page, ActivityLogs::LOGS_PER_PAGE);

            return $collection;
        } else {
            throw new ImpossibleCollectionError('User not found or not active');
        }
    }

    private static function prepareRangeActivityLogsForOrByCursorCollection(string $collection_name, User $user)
    {
        $bits = explode('_', $collection_name);

        $range_dates = array_pop($bits);

        $for = DataObjectPool::get(User::class, array_pop($bits));

        if ($for instanceof User && $for->isActive()) {
            if (str_starts_with($collection_name, 'cursor_range_activity_logs_for')) {
                $collection = new RangeUserActivityLogsForCursorCollection($collection_name);
            } else {
                $collection = new RangeUserActivityLogsByCursorCollection($collection_name);
            }

            $collection
                ->setWhosAsking($user)
                ->setForOrBy($for)
                ->setDates($range_dates);

            return $collection;
        } else {
            throw new ImpossibleCollectionError('User not found or not active');
        }
    }

    private static function prepareRangeActivityLogsForCollection(
        string $collection_name,
        User $user
    ): RangeUserActivityLogsForCollection
    {
        $bits = explode('_', $collection_name);

        $page = array_pop($bits); // Remove page #
        array_pop($bits); // Remove _page_
        $range_dates = array_pop($bits);

        $for = DataObjectPool::get('User', array_pop($bits));

        if ($for instanceof User && $for->isActive()) {
            $collection = new RangeUserActivityLogsForCollection($collection_name);

            $collection
                ->setWhosAsking($user)
                ->setForOrBy($for)
                ->setDates($range_dates)
                ->setPagination($page, ActivityLogs::LOGS_PER_PAGE);

            return $collection;
        } else {
            throw new ImpossibleCollectionError('User not found or not active');
        }
    }

    /**
     * Prepare daily activity logs for collection.
     *
     * @param  string                     $collection_name
     * @param  User|null                  $user
     * @return UserActivityLogsCollection
     */
    private static function prepareDailyActivityLogsForCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        $page = array_pop($bits);
        array_pop($bits); // _page_

        $day = DateValue::makeFromString(array_pop($bits));
        $for = DataObjectPool::get('User', array_pop($bits));

        if ($for instanceof User && $for->isActive()) {
            $collection = new DailyUserActivityLogsForCollection($collection_name);

            $collection->setForOrBy($for)->setWhosAsking($user)->setDay($day);
            $collection->setPagination($page, ActivityLogs::LOGS_PER_PAGE);

            return $collection;
        } else {
            throw new ImpossibleCollectionError('User not found or not active');
        }
    }

    /**
     * Prepare activity logs by collection.
     *
     * @param  string                     $collection_name
     * @param  User|null                  $user
     * @return UserActivityLogsCollection
     */
    private static function prepareActivityLogsByCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        $page = array_pop($bits);
        array_pop($bits); // Remove _page_

        $for = DataObjectPool::get('User', array_pop($bits));

        if ($for instanceof User && $for->isActive()) {
            $collection = new UserActivityLogsByCollection($collection_name);

            $collection->setPagination($page, ActivityLogs::LOGS_PER_PAGE);
            $collection->setWhosAsking($user);
            $collection->setForOrBy($for);

            return $collection;
        } else {
            throw new ImpossibleCollectionError('User not found or not active');
        }
    }

    /**
     * Prepare activity logs collection for date range.
     *
     * @param  string                     $collection_name
     * @param  User|null                  $user
     * @return UserActivityLogsCollection
     */
    private static function prepareRangeActivityLogsByCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        $page = array_pop($bits);
        array_pop($bits); // Remove _page_
        $range_dates = array_pop($bits);

        $for = DataObjectPool::get(User::class, array_pop($bits));

        if ($for instanceof User && $for->isActive()) {
            $collection = new RangeUserActivityLogsByCollection($collection_name);

            $collection
                ->setForOrBy($for)
                ->setDates($range_dates)
                ->setWhosAsking($user)
                ->setPagination($page, ActivityLogs::LOGS_PER_PAGE);

            return $collection;
        } else {
            throw new ImpossibleCollectionError('User not found or not active');
        }
    }

    /**
     * Prepare all, active or archived users collection.
     *
     * @param  string          $collection_name
     * @param  User|null       $user
     * @return ModelCollection
     */
    private static function prepareUsersCollection($collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        $collection->setPreExecuteCallback(function ($ids) {
            if (empty($ids)) {
                return;
            }

            self::preloadLastLoginOn($ids);
            UserInvitations::preloadUserInvitationMap($ids);
            self::preloadAdditionalEmailAddresses($ids);
            UserWorkspaces::preloadUserWorkspaceCountMap($ids);
        });

        switch ($collection_name) {
            case DataManager::ALL:
                $collection->setConditions(
                    'id IN (?)',
                    self::prepareVisibleUserIdsValue($user, STATE_TRASHED)
                );

                break;
            case self::ACTIVE:
                $collection->setConditions(
                    'id IN (?) AND is_archived = ? AND is_trashed = ?',
                    self::prepareVisibleUserIdsValue($user, STATE_VISIBLE),
                    false,
                    false
                );

                break;
            case self::ARCHIVED:
                $collection->setConditions(
                    'id IN (?) AND is_archived = ? AND is_trashed = ?',
                    self::prepareVisibleUserIdsValue($user, STATE_ARCHIVED),
                    true,
                    false
                );

                break;
            default:
                throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    private static function prepareVisibleUserIdsValue(User $user, string $state): array
    {
        return count($user->getVisibleUserIds($state)) ? $user->getVisibleUserIds($state) : [0];
    }

    /**
     * Prepare project ID-s filter by user.
     *
     * @return array|true
     */
    public static function prepareProjectIdsFilterByUser(User $user)
    {
        if ($user->isOwner()) {
            return true;
        }

        $project_ids = $user->isMember() || $user->isPowerClient(true) ? $user->getProjectIds() : null;

        if (empty($project_ids)) {
            throw new ImpossibleCollectionError('User has no projects.');
        }

        return $project_ids;
    }

    public static function canAdd(User $user): bool
    {
        return $user->isOwner() || $user->isPowerUser(true);
    }

    /**
     * Return true if $user can create a new user account with specified role and permissions.
     *
     * @param  string $role
     * @param  array  $custom_permissions
     * @return bool
     */
    public static function canAddAs(User $user, $role, $custom_permissions = [])
    {
        if (empty($custom_permissions)) {
            $custom_permissions = [];
        }

        if (self::isAvailableUserClass($role)) {
            if (self::canAdd($user)) {
                if ($user->isOwner()) {
                    return true;
                } else {
                    if ($role === Owner::class) {
                        return false; // Members can't invite owners
                    } elseif ($role === Member::class) {
                        return empty($custom_permissions); // Members can't invite members with extra permissions
                    } else {
                        return true;
                    }
                }
            }

            return false;
        }

        throw new InvalidParamError('role', $role, 'Invalid role name');
    }

    /**
     * Invite a group of people.
     *
     * @param  string[]   $email_addresses
     * @param  string     $role
     * @param  string[]   $custom_permissions
     * @param  array|null $additional
     * @return User[]
     */
    public static function invite(User $by, $email_addresses, $role, $custom_permissions, $additional = null)
    {
        $email_addresses = (array) $email_addresses;

        if (empty($role) || !self::isAvailableUserClass($role)) {
            throw new InvalidParamError('role', $role);
        }

        self::checkCustomPermissionsForRole($role, $custom_permissions);

        if (empty($additional)) {
            $additional = [];
        }

        if (AngieApplication::isOnDemand() && !OnDemand::canAddUsersBasedOnCurrentPlan($role, $custom_permissions, self::countValidEmailAddresses($email_addresses), $email_addresses)) {
            throw new Error("Can't invite users, check your plan restriction.");
        }

        /** @var User[] $invitees */
        $invitees = [];

        /** @var User[] $all_users */
        $all_users = [];

        try {
            DB::beginWork('Begin: invite users @ ' . __CLASS__);

            $users_to_invite = self::addressesListToUsersToInvite($email_addresses);

            $projects = self::prepareAdditionalForInvite($additional); // Get projects and prepare $additional

            foreach ($users_to_invite as $email_address => $name) {
                [$first_name, $last_name] = $name;

                $user = self::findByEmail($email_address);

                if ($user instanceof User) {
                    if ($user->getIsTrashed() || $user->getIsArchived()) {
                        DataManager::reactivate($user);

                        if ($user->canChangeRole($by, !empty($custom_permissions))) { // You shouldn't change owner to "smaller" role if you aren't owner
                            $user = self::changeUserType($user, $role, $custom_permissions, $by);
                            $user = self::update($user, ['company_id' => $additional['company_id']]);
                        }

                        if ($user->isPendingActivation()) { //if already invited but not accepted - invite him again
                            $invitees[] = $user;
                        }

                        $all_users[] = $user;
                    }
                } else {
                    $user_instance = self::create(
                        array_merge(
                            $additional,
                            [
                                'type' => $role,
                                'email' => $email_address,
                                'first_name' => $first_name,
                                'last_name' => $last_name,
                                'password' => AngieApplication::authentication()->generateStrongPassword(32),
                                'custom_permissions' => $custom_permissions,
                                'language_id' => Languages::findDefault()->getId(),
                            ]
                        )
                    );
                    $invitees[] = $user_instance;
                    $all_users[] = $user_instance;
                }
            }

            self::sendInvitationsToNewUsers($invitees, $additional, $by); // Invite new users
            self::addInvitedUsersToProjects($projects, $all_users); // Make sure that all users are added to all projects

            DB::commit('Done: invite users @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: invite users @ ' . __CLASS__);

            throw $e;
        }

        return $all_users;
    }

    /**
     * Check if values listed in custom permissions are applicable to $role.
     *
     * @param string $role
     * @param mixed  $custom_permissions
     */
    private static function checkCustomPermissionsForRole($role, $custom_permissions)
    {
        if ($custom_permissions && is_foreachable($custom_permissions)) {
            $test_instance = new $role();

            if ($test_instance instanceof User) {
                foreach ($custom_permissions as $custom_permission) {
                    if (!$test_instance->isCustomPermission($custom_permission)) {
                        throw new InvalidParamError('custom_permissions', $custom_permission);
                    }
                }
            }
        }
    }

    /**
     * Return a total number of valid email addresses from an array of addresses.
     *
     * @param  string[] $email_addresses
     * @return int
     */
    private static function countValidEmailAddresses($email_addresses)
    {
        $valid_email_addresses = 0;

        if (is_foreachable($email_addresses)) {
            foreach ($email_addresses as $email_address) {
                if (is_valid_email(trim($email_address))) {
                    ++$valid_email_addresses;
                }
            }
        }

        return $valid_email_addresses;
    }

    /**
     * Parse input array of addresses to invite and return a tidy list.
     *
     * @param  array $email_addresses
     * @return array
     */
    private static function addressesListToUsersToInvite($email_addresses)
    {
        $users_to_invite = [];

        if (is_array($email_addresses) && count($email_addresses)) {
            require_once ANGIE_PATH . '/classes/RFC822.php';

            foreach ($email_addresses as $email_address) {
                try {
                    $parsed_addresses = RFC822::parseAddressList($email_address, 'localhost', true, false);
                } catch (Exception $e) {
                    throw new InvalidParamError('email_addresses', $email_address);
                }

                if ($parsed_addresses && is_foreachable($parsed_addresses)) {
                    foreach ($parsed_addresses as $address_or_a_group) {
                        if (isset($address_or_a_group->addresses)) {
                            foreach ($address_or_a_group->addresses as $address) {
                                $users_to_invite[strtolower($address->mailbox . '@' . $address->host)] = trim($address->personal);
                            }
                        } else {
                            $users_to_invite[strtolower($address_or_a_group->mailbox . '@' . $address_or_a_group->host)] = trim($address_or_a_group->personal);
                        }
                    }
                } else {
                    throw new InvalidParamError('email_addresses', $email_address);
                }
            }

            foreach ($users_to_invite as $email => $name) {
                if ($name) {
                    $bits = explode(' ', $name);
                    $users_to_invite[$email] = [array_shift($bits), implode(' ', $bits)];
                } else {
                    $users_to_invite[$email] = [null, null];
                }
            }
        } else {
            throw new InvalidParamError('email_addresses', $email_addresses);
        }

        return $users_to_invite;
    }

    /**
     * Prepare additional attributes for invitation process.
     *
     * @return Project[]|null
     */
    private static function prepareAdditionalForInvite(array &$additional)
    {
        $company = isset($additional['company_id']) ? DataObjectPool::get(Company::class, $additional['company_id']) : null;

        $additional['company_id'] = $company instanceof Company ? $company->getId() : 0;

        if (array_key_exists('invite_to', $additional)) {
            unset($additional['invite_to']);
        }

        $projects = null;

        if (isset($additional['project_ids'])) {
            $projects = is_foreachable($additional['project_ids']) ? Projects::findByIds($additional['project_ids']) : null;

            /** @var DBResult|Project[] $projects */
            if ($projects instanceof DBResult && $projects->count()) {
                $additional['invite_to'] = first($projects);
            }

            unset($additional['project_ids']);
        }

        return $projects;
    }

    /**
     * Invite new users.
     *
     * @param User[] $invitees
     */
    private static function sendInvitationsToNewUsers($invitees, array $additional, User $by)
    {
        if (count($invitees)) {
            if (empty($additional['invite_to'])) {
                $additional['invite_to'] = null;
            }

            foreach ($invitees as $invitee) {
                $invitee->invite($by, $additional['invite_to'], true);
            }
        }
    }

    /**
     * Make sure that all users are added to all projects.
     *
     * @param Project[] $projects
     * @param User[]    $users
     */
    private static function addInvitedUsersToProjects($projects, $users)
    {
        if ($projects) {
            foreach ($projects as $project) {
                $project->addMembers($users, ['send_invitations' => false]);
            }
        }
    }

    // ---------------------------------------------------
    //  Roles, Permissions, Instances
    // ---------------------------------------------------

    /**
     * Return available user classes.
     *
     * @return array
     */
    public static function getAvailableUserClasses()
    {
        return [
            Owner::class,
            Member::class,
            Client::class,
        ];
    }

    /**
     * Return array of available user instances.
     *
     * @return User[]
     */
    public static function getAvailableUserInstances()
    {
        return [
            new Owner(),
            new Member(),
            new Client(),
        ];
    }

    /**
     * Return default user class.
     *
     * @return string
     */
    public static function getDefaultUserClass()
    {
        return Client::class;
    }

    /**
     * Returns true if $class is available user class.
     *
     * @param  string $class
     * @return bool
     */
    public static function isAvailableUserClass($class)
    {
        return in_array($class, self::getAvailableUserClasses());
    }

    /**
     * Return user instance.
     *
     * Use $of_class to specify user class, if needed. When omitted, default user instance will be created
     *
     * @param  string $of_class
     * @param  bool   $validate
     * @return User
     */
    public static function getUserInstance($of_class = null, $validate = false)
    {
        if (empty($of_class)) {
            $of_class = static::getDefaultUserClass();
        }

        if ($validate && !self::isAvailableUserClass($of_class)) {
            throw new InvalidParamError('of_class', $of_class, "'$of_class' is not a valid user class");
        }

        return new $of_class();
    }

    public static function changeUserType(
        User $user,
        string $new_class,
        array $custom_permissions,
        User $by
    ): User
    {
        if (self::isAvailableUserClass($new_class)) {
            if ($new_class == Owner::class && !$by->isOwner()) {
                throw new InvalidInstanceError('by', $by, Owner::class);
            }

            if (self::isLastOwner($user)) {
                throw new LastOwnerRoleChangeError($user);
            }

            AngieApplication::cache()->removeByObject($user);

            if (empty($custom_permissions)) {
                $custom_permissions = [];
            }

            $from_class = get_class($user);

            try {
                DB::beginWork('Begin: change user type @ ' . __CLASS__);

                if (self::shouldRevokeClientPlusAccess($user, $new_class, $custom_permissions)) {
                    self::revokeClientPlusAccess($user, $by);
                }

                $old_user = clone $user;

                if (get_class($user) != $new_class) {
                    DB::execute('UPDATE `users` SET `type` = ? WHERE `id` = ?', $new_class, $user->getId());

                    /** @var User $user */
                    $user = DataObjectPool::reload(
                        User::class,
                        $user->getId()
                    );
                }

                $user->setSystemPermissions($custom_permissions);
                $user->save();

                if (AngieApplication::isOnDemand()) {
                    self::checkIsChargeableRoleChanged($old_user, $user);
                }

                if (self::shouldRevokeMemberAccess($from_class, $new_class)) {
                    self::revokeMemberAccess($user, $custom_permissions, $by);
                }

                DB::commit('Done: change user type @ ' . __CLASS__);

                if (AngieApplication::isOnDemand()) {
                    AngieApplication::shepherdSyncer()->syncUserRole($user);
                }

                if ($user instanceof User) {
                    DataObjectPool::announce(new UserUpdatedEvent($user));
                }

                return $user;
            } catch (Exception $e) {
                DB::rollback('Rollback: change user type @ ' . __CLASS__);

                throw $e;
            }
        }

        throw new InvalidParamError('new_class', $new_class, sprintf('"%s" is not a valid user class', $new_class));
    }

    private static function checkIsChargeableRoleChanged(User $old_user, User $user)
    {
        /** @var User $user */
        $user = DataObjectPool::get(User::class, $user->getId(), null, true);

        if (!$old_user->isChargeable() && $user->isChargeable()) {
            AngieApplication::eventsDispatcher()->trigger(
                new ChargeableUserActivatedEvent(
                    $user,
                    ChargeableUserActivatedEventInterface::USER_ROLE_UPGRADED_REASON
                )
            );
        }

        if ($old_user->isChargeable() && !$user->isChargeable()) {
            AngieApplication::eventsDispatcher()->trigger(
                new ChargeableUserDeactivatedEvent(
                    $user,
                    ChargeableUserDeactivatedEventInterface::USER_ROLE_DOWNGRADED_REASON
                )
            );
        }
    }

    /**
     * Return true if client+ access got revoked from the user.
     */
    private static function shouldRevokeClientPlusAccess(User $user, string $new_class, array $custom_permissions): bool
    {
        return $user->isPowerClient(true)
            && !in_array(User::CAN_MANAGE_TASKS, $custom_permissions)
            && $new_class === Client::class;
    }

    /**
     * Revoke Client+ access when they lose + permissions.
     */
    private static function revokeClientPlusAccess(User $revoke_access_from, User $by)
    {
        Tasks::revokeAssignee($revoke_access_from, $by);
        Subtasks::revokeAssignee($revoke_access_from, $by);
        RecurringTasks::revokeAssignee($revoke_access_from, $by);
        ProjectTemplateElements::revokeAssignee($revoke_access_from->getId());
    }

    /**
     * Return true if we should revoke member like access (access to hidden objects, assignments, team memberships) on user type change.
     */
    private static function shouldRevokeMemberAccess(
        string $from_class,
        string $new_class
    ): bool
    {
        return $new_class === Client::class && $new_class != $from_class;
    }

    /**
     * Revoke member level access from $user (access to hidden objects, assignments, team memberships).
     */
    private static function revokeMemberAccess(
        User $user,
        array $custom_permissions,
        User $by
    ): void
    {
        // remove project leader.
        DB::execute(
            'UPDATE `projects` SET `leader_id` = ?, `updated_on` = ? WHERE `leader_id` = ?',
            0,
            new DateTimeValue(),
            $user->getId()
        );

        AngieApplication::cache()->removeByModel('projects');

        $private_objects = [];

        $tables_with_private_objects = [
            Task::class => 'tasks',
            RecurringTask::class => 'recurring_tasks',
            Discussion::class => 'discussions',
            File::class => 'files',
            Note::class => 'notes',
        ];

        // prepare all hidden objects
        foreach ($tables_with_private_objects as $class => $table) {
            $ids = DB::executeFirstColumn(
                sprintf('SELECT `id` FROM %s WHERE `is_hidden_from_clients` = ?', $table),
                true
            );

            if (!empty($ids)) {
                $private_objects[$class] = $ids;
            }
        }

        if (!empty($private_objects[Task::class])) {
            $subtask_ids = DB::executeFirstColumn(
                'SELECT `id` FROM `subtasks` WHERE `task_id` IN (?)',
                $private_objects[Task::class]
            );

            if (!empty($subtask_ids)) {
                $private_objects[Subtask::class] = $subtask_ids;
            }
        }

        // remove from hidden objects
        if (count($private_objects)) {
            Subscriptions::deleteByParents($private_objects, $user);

            // remove both client or client+ from hidden tasks
            if (!empty($private_objects[Task::class]) && count($private_objects[Task::class]) &&
                $tasks = Tasks::find(
                    [
                        'conditions' => [
                            '`id` IN (?) AND `assignee_id` = ?',
                            $private_objects[Task::class],
                            $user->getId(),
                        ],
                    ]
                )
            ) {
                foreach ($tasks as $task) {
                    $task->setAssignee(null, $by);
                }
            }

            // remove both client or client+ from hidden subtasks
            if (!empty($private_objects[Subtask::class]) && count($private_objects[Subtask::class]) &&
                $subtasks = Subtasks::find(
                    [
                        'conditions' => [
                            '`id` IN (?) AND `assignee_id` = ?',
                            $private_objects[Subtask::class],
                            $user->getId(),
                        ],
                    ]
                )
            ) {
                foreach ($subtasks as $subtask) {
                    $subtask->setAssignee(null, $by);
                }
            }

            // remove both client or client+ from hidden recurring tasks and subtasks
            if (!empty($private_objects[RecurringTask::class]) && count($private_objects[RecurringTask::class]) &&
                /** @var RecurringTask[] $recurring_tasks */
                $recurring_tasks = RecurringTasks::find(
                    [
                        'conditions' => [
                            '`id` IN (?)',
                            $private_objects[RecurringTask::class],
                        ],
                    ]
                )
            ) {
                foreach ($recurring_tasks as $recurring_task) {
                    $recurring_task->removeAssignee($user, $by);
                }
            }
        }

        // if role changed to client, remove from all other not hidden tasks, subtasks and recurring tasks
        if (!in_array(User::CAN_MANAGE_TASKS, $custom_permissions)) {
            /** @var Task[] $tasks */
            $tasks = Tasks::find(
                [
                    'conditions' => [
                        '`assignee_id` = ?',
                        $user->getId(),
                    ],
                ]
            );

            if ($tasks) {
                foreach ($tasks as $task) {
                    $task->setAssignee(null, $by);
                }
            }

            /** @var Subtask[] $subtasks */
            $subtasks = Subtasks::find(
                [
                    'conditions' => [
                        '`assignee_id` = ?',
                        $user->getId(),
                    ],
                ]
            );

            if ($subtasks) {
                foreach ($subtasks as $subtask) {
                    $subtask->setAssignee(null, $by);
                }
            }

            RecurringTasks::revokeAssignee($user, $by);
        }

        // remove both client or client+ from teams
        DB::executeFirstRow('DELETE FROM `team_users` WHERE `user_id` = ?', $user->getId());
        AngieApplication::cache()->remove(Teams::getCacheKey());
    }

    // ---------------------------------------------------
    //  Finders
    // ---------------------------------------------------

    /**
     * Return users by company.
     *
     * If $ids is set, result will be limited to these users only
     *
     * @param  array           $ids
     * @param  int             $min_state
     * @return DbResult|User[]
     */
    public static function findByCompany(Company $company, $ids = [], $min_state = STATE_VISIBLE)
    {
        if ($ids && is_foreachable($ids)) {
            switch ($min_state) {
                case STATE_TRASHED:
                    $conditions = ['company_id = ? AND id IN (?)', $company->getId(), $ids];

                    break;
                case STATE_ARCHIVED:
                    $conditions = ['company_id = ? AND id IN (?) AND is_archived = ?', $company->getId(), $ids, false];

                    break;
                default:
                    $conditions = ['company_id = ? AND id IN (?) AND is_archived = ? AND is_trashed = ?', $company->getId(), $ids, false, false];

                    break;
            }
        } else {
            switch ($min_state) {
                case STATE_TRASHED:
                    $conditions = ['company_id = ?', $company->getId()];

                    break;
                case STATE_ARCHIVED:
                    $conditions = ['company_id = ? AND is_archived = ?', $company->getId(), false];

                    break;
                default:
                    $conditions = ['company_id = ? AND is_archived = ? AND is_trashed = ?', $company->getId(), false, false];

                    break;
            }
        }

        return self::find(['conditions' => $conditions]);
    }

    /**
     * Return number of active users in the system.
     *
     * @return int
     */
    public static function countActiveUsers()
    {
        $active_users_count = (int) DB::executeFirstCell('SELECT COUNT(id) FROM users WHERE is_archived = ? AND is_trashed = ? AND type != ?', false, false, Client::class);

        /** @var Client[] $clients */
        if ($clients = static::findByType(Client::class)) {
            foreach ($clients as $client) {
                if ($client->canManageTasks()) {
                    ++$active_users_count;
                }
            }
        }

        return $active_users_count;
    }

    public static function countChargeableUsers(): int
    {
        return count(self::getChargeableUsers());
    }

    public static function getChargeableUsers(): array
    {
        $chargeable = [];

        /** @var User[] $users */
        $users = Users::find(
            [
                'conditions' => [
                    'is_archived = ? AND is_trashed = ?',
                    false,
                    false,
                ],
            ]
        );

        if ($users) {
            foreach ($users as $user) {
                if ($user->isChargeable()) {
                    $chargeable[] = $user;
                }
            }
        }

        return $chargeable;
    }

    public static function getChargeableUsersEliqiableForCovidDiscount()
    {
        $chargeable = [];

        /** @var User[] $users */
        $users = Users::find(
            [
                'conditions' => [
                    'is_archived = ? AND is_trashed = ? AND is_eligible_for_covid_discount = ?',
                    false,
                    false,
                    true,
                ],
            ]
        );

        if ($users) {
            foreach ($users as $user) {
                if ($user->isChargeable()) {
                    $chargeable[] = $user;
                }
            }
        }

        return $chargeable;
    }

    public static function findActiveOwnersAndMembersIds(): array
    {
        return (array) DB::executeFirstColumn(
            'SELECT id FROM users WHERE is_archived = ? AND is_trashed = ? AND type != ?',
            false,
            false,
            Client::class
        );
    }

    public static function countChargeableUsersEliqiableForCovidDiscount()
    {
        return count(self::getChargeableUsersEliqiableForCovidDiscount());
    }

    /**
     * Return number of active clients in the system.
     *
     * @return int
     */
    public static function countActiveClients()
    {
        return (int) DB::executeFirstCell('SELECT COUNT(id) FROM users WHERE is_archived = ? AND is_trashed = ? AND type = ?', false, false, Client::class);
    }

    /**
     * Return all users with uploaded avatar.
     *
     * @return DbResult|User[]
     */
    public static function findUsersWithUploadedAvatar()
    {
        return static::find(['conditions' => ['avatar_location IS NOT NULL AND avatar_location != ?', '']]);
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Cached permissions.
     *
     * @var NamedList
     */
    private static $permissions = false;

    /**
     * Return list of system permission with their details.
     *
     * @return NamedList
     */
    public static function getPermissions()
    {
        if (self::$permissions === false) {
            self::$permissions = new NamedList([]);

            Angie\Events::trigger('on_system_permissions', [&self::$permissions]);
        }

        return self::$permissions;
    }

    /**
     * Return defaults of a single permission.
     *
     * @param  string $name
     * @return array
     */
    public static function getPermission($name)
    {
        return static::getPermissions()->get($name);
    }

    /**
     * Return names of all system permissions.
     *
     * @return array
     */
    public static function getPermissionNames()
    {
        return static::getPermissions()->keys();
    }

    // ---------------------------------------------------
    //  Utils
    // ---------------------------------------------------

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): User
    {
        try {
            DB::beginWork('Creating user @ ' . __CLASS__);

            $uploaded_file = isset($attributes['uploaded_avatar_code']) && $attributes['uploaded_avatar_code']
                ? UploadedFiles::findByCode($attributes['uploaded_avatar_code'])
                : null;

            if ($uploaded_file instanceof WarehouseUploadedFile) {
                $attributes['avatar_location'] = $uploaded_file->getLocation();
                $attributes['avatar_md5'] = $uploaded_file->getMd5();
            } else {
                $attributes['avatar_location'] = $uploaded_file instanceof UploadedFile ? $uploaded_file->getLocation() : '';
            }

            $user = parent::create($attributes, false, false);

            if ($user instanceof User) {
                if (isset($attributes['custom_permissions']) && is_foreachable($attributes['custom_permissions'])) {
                    $user->setSystemPermissions($attributes['custom_permissions']);
                }
                if (isset($attributes['avatar_md5'])) {
                    $user->setAvatarMd5($attributes['avatar_md5']);
                }

                if ($save) {
                    $user->save();
                }

                if ($user->isLoaded()) {
                    if (isset($attributes['additional_email_addresses']) && is_foreachable($attributes['additional_email_addresses'])) {
                        $user->setAdditionalEmailAddresses($attributes['additional_email_addresses']);
                    }
                }

                if ($uploaded_file) {
                    $uploaded_file->keepFileOnDelete(true);
                    $uploaded_file->delete();
                }
            }

            DB::commit('User created @ ' . __CLASS__);

            if ($announce) {
                DataObjectPool::announce(new UserCreatedEvent($user));
            }

            return $user;
        } catch (Exception $e) {
            DB::rollback('Failed to create user @ ' . __CLASS__);

            throw $e;
        }
    }

    public static function updateUsersLastLoginOn(DateTimeValue $now): void
    {
        DB::execute(
            'UPDATE `users`
             SET `first_login_on` = ?, `last_login_on` = ?
             WHERE (`type` IN (?) OR (`raw_additional_properties` LIKE ? AND `type` = ?))
             AND `is_archived` = ?
             AND `is_trashed` = ?
             AND `last_login_on` IS NULL',
            $now->toMySQL(),
            $now->toMySQL(),
            [
                Owner::class,
                Member::class,
            ],
            '%can_manage_tasks%',
            Client::class,
            false,
            false
        );
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): User
    {
        if ($instance instanceof User) {
            $old_company_id = $instance->getCompanyId();

            if (!$instance->isExampleUser() && !empty($attributes['email']) && Users::isExampleEmail($attributes['email'])) {
                throw new InvalidArgumentException(lang("Can't edit user email to @example.com"));
            }

            try {
                DB::beginWork('Updating user @ ' . __CLASS__);

                $current_avatar_location = $instance->getAvatarLocation();
                $current_avatar_type = $instance->getAvatarType();

                $uploaded_file = isset($attributes['uploaded_avatar_code']) && $attributes['uploaded_avatar_code']
                    ? UploadedFiles::findByCode($attributes['uploaded_avatar_code'])
                    : null;

                if ($uploaded_file instanceof WarehouseUploadedFile) {
                    $attributes['avatar_location'] = $uploaded_file->getLocation();
                    $attributes['avatar_md5'] = $uploaded_file->getMd5();
                } elseif ($uploaded_file instanceof UploadedFile) {
                    $attributes['avatar_location'] = $uploaded_file->getLocation();
                } else {
                    if (array_key_exists('avatar_location', $attributes)) {
                        unset($attributes['avatar_location']);
                    }
                }

                if (isset($attributes['custom_permissions']) && is_array($attributes['custom_permissions'])) {
                    $instance->setSystemPermissions($attributes['custom_permissions']);
                }
                if (isset($attributes['avatar_md5'])) {
                    $instance->setAvatarMd5($attributes['avatar_md5']);
                }

                if (AngieApplication::isOnDemand() && isset($attributes['password'])) {
                    AngieApplication::shepherdSyncer()->changeUserPassword(
                        $instance,
                        $instance->getPassword(),
                        $attributes['password'],
                        $attributes['password']
                    );
                }

                if (
                    AngieApplication::isOnDemand() &&
                    (
                        isset($attributes['email']) ||
                        isset($attributes['first_name']) ||
                        isset($attributes['last_name'])
                    )
                ) {
                    AngieApplication::shepherdSyncer()->changeUserProfile($instance,
                        [
                            'email' => !empty($attributes['email']) ? $attributes['email'] : $instance->getEmail(),
                            'first_name' => !empty($attributes['first_name']) ? $attributes['first_name'] : $instance->getFirstName(),
                            'last_name' => !empty($attributes['last_name']) ? $attributes['last_name'] : $instance->getLastName(),
                        ]
                    );
                }

                $old_instance = clone $instance;

                parent::update($instance, $attributes, $save);

                if ($instance->isChargeable() && !$old_instance->isChargeable()) {
                    if (AngieApplication::isOnDemand()) {
                        AngieApplication::eventsDispatcher()->trigger(new ChargeableUserActivatedEvent(
                            $instance,
                            ChargeableUserActivatedEventInterface::USER_AT_EXAMPLE_ACTIVATED_REASON
                        ));
                    }
                }

                if ($save && array_key_exists('additional_email_addresses', $attributes)) {
                    $instance->setAdditionalEmailAddresses($attributes['additional_email_addresses']);
                }

                if ($uploaded_file) {
                    if ($current_avatar_location) {
                        AngieApplication::storage()->deleteFile(
                            $current_avatar_type,
                            $current_avatar_location
                        );
                    }

                    $uploaded_file->keepFileOnDelete(true);
                    $uploaded_file->delete();
                }

                DB::commit('User updated @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Failed to update user @ ' . __CLASS__);

                throw $e;
            }

            // When user change company, clear cache related to old and new company object
            if ($old_company_id != $instance->getCompanyId()) {
                if ($old_company = DataObjectPool::get(Company::class, $old_company_id)) {
                    AngieApplication::cache()->removeByObject($old_company);

                    if ($old_company->getIsOwner() && $general = Conversations::getGeneralConversation()) {
                        Conversations::clearCacheFor($general->getId());
                    }
                }

                if ($new_company = DataObjectPool::get(Company::class, $instance->getCompanyId())) {
                    AngieApplication::cache()->removeByObject($new_company);

                    if ($new_company->getIsOwner() && $general = Conversations::getGeneralConversation()) {
                        Conversations::clearCacheFor($general->getId());
                    }
                }
            }
        } else {
            throw new InvalidInstanceError('instance', $instance, User::class);
        }

        if ($save) {
            DataObjectPool::announce(new UserUpdatedEvent($instance));
        }

        return $instance;
    }

    /**
     * Return user display name by user ID.
     *
     * @param  int    $id
     * @param  bool   $short
     * @return string
     */
    public static function getUserDisplayNameById($id, $short = false)
    {
        $user = DB::executeFirstRow('SELECT first_name, last_name, email FROM users WHERE id = ?', $id);

        if ($user) {
            return self::getUserDisplayName($user, $short);
        } else {
            return null;
        }
    }

    /**
     * Return display name of user based on given parameters.
     *
     * @param  array  $params
     * @param  bool   $short
     * @return string
     */
    public static function getUserDisplayName($params, $short = false)
    {
        $full_name = isset($params['full_name']) && $params['full_name'] ? $params['full_name'] : null;
        $first_name = isset($params['first_name']) && $params['first_name'] ? $params['first_name'] : null;
        $last_name = isset($params['last_name']) && $params['last_name'] ? $params['last_name'] : null;
        $email = isset($params['email']) && $params['email'] ? $params['email'] : null;

        if ($short) {
            if ($full_name) {
                $parts = explode(' ', $full_name);

                if (count($params) > 1) {
                    $first_name = array_shift($parts);
                    $last_name = implode(' ', $parts);
                } else {
                    $first_name = $full_name;
                }
            }

            if ($first_name && $last_name) {
                return $first_name . ' ' . substr_utf($last_name, 0, 1) . '.';
            } elseif ($first_name) {
                return $first_name;
            } elseif ($last_name) {
                return $last_name;
            } else {
                return substr($email, 0, strpos($email, '@'));
            }
        } else {
            if ($full_name) {
                return $full_name;
            } elseif ($first_name && $last_name) {
                return $first_name . ' ' . $last_name;
            } elseif ($first_name) {
                return $first_name;
            } elseif ($last_name) {
                return $last_name;
            } else {
                return substr($email, 0, strpos($email, '@'));
            }
        }
    }

    /**
     * Return user ID name map.
     *
     * @param  array $ids
     * @param  bool  $short
     * @return array
     */
    public static function getIdNameMap($ids = null, $short = false)
    {
        if ($ids) {
            $rows = DB::execute('SELECT id, first_name, last_name, email FROM users WHERE id IN (?) ORDER BY order_by', $ids);
        } else {
            $rows = DB::execute('SELECT id, first_name, last_name, email FROM users ORDER BY order_by');
        }

        $result = [];

        if ($rows) {
            foreach ($rows as $row) {
                $result[$row['id']] = self::getUserDisplayName($row, $short);
            }
        }

        return $result;
    }

    /**
     * Return users by user type.
     *
     * @param  string|string[] $types
     * @param  mixed           $additional_conditions
     * @return DbResult|User[]
     */
    public static function findByType($types, $additional_conditions = null)
    {
        $conditions = DB::prepare('type IN (?) AND is_archived = ? AND is_trashed = ?', $types, false, false);

        if ($additional_conditions) {
            $conditions = '(' . $conditions . ' AND (' . DB::prepareConditions($additional_conditions) . '))';
        }

        return self::find(['conditions' => $conditions]);
    }

    /**
     * Return all owner ids in system.
     *
     * @return array
     */
    public static function findOwnerIds()
    {
        return DB::executeFirstColumn(
            'SELECT id
                FROM users
                WHERE type = ? AND is_archived = ? AND is_trashed = ?',
            Owner::class,
            false,
            false
        );
    }

    /**
     * Return all owners in system.
     *
     * @return DbResult|Owner[]
     */
    public static function findOwners()
    {
        return self::find([
            'conditions' => [
                'type = ? AND is_archived = ? AND is_trashed = ?',
                Owner::class,
                false,
                false,
            ],
        ]);
    }

    /**
     * Find first usable Owner instance.
     *
     * @return User|null
     */
    public static function findFirstOwner()
    {
        return self::findOne(
            [
                'conditions' => ['type = ? AND is_archived = ? AND is_trashed = ?', Owner::class, false, false],
                'order' => 'id',
            ]
        );
    }

    public function getFirstOwnerId()
    {
        return DB::executeFirstCell(
            'SELECT id
                FROM users
                WHERE type = ? AND is_archived = ? AND is_trashed = ?
                ORDER BY id
                LIMIT 0, 1',
            Owner::class,
            false,
            false
        );
    }

    /**
     * Returns true if $user is last owner in the system.
     *
     * @return bool
     */
    public static function isLastOwner(User $user)
    {
        return $user instanceof Owner && self::count(['type = ? AND is_archived = ? AND is_trashed = ?', Owner::class, false, false]) == 1;
    }

    /**
     * @param  int    $user_id
     * @param  string $code
     * @param  string $new_password
     * @return User
     */
    public static function finishPasswordRecovery($user_id, $code, $new_password)
    {
        $user = DataObjectPool::get(User::class, $user_id);

        if (!$user instanceof User || !$user->isActive()) {
            throw new ResetPasswordError(ResetPasswordError::USER_NOT_ACTIVE);
        }

        if (!$user->validatePasswordRecoveryCode($code)) {
            throw new ResetPasswordError(ResetPasswordError::INVALID_CODE);
        }

        try {
            $user->finishPasswordRecovery($new_password);
        } catch (ValidationErrors $e) {
            if (!empty($e->getFieldErrors('password'))) {
                throw new ResetPasswordError(ResetPasswordError::INVALID_PASSWORD, null, $e);
            } else {
                throw $e;
            }
        }

        return $user;
    }

    /**
     * Find user ID-s by given type filter.
     *
     * @param  string              $type
     * @param  User|int|array|null $exclude
     * @param  callable            $filter
     * @return array
     */
    public static function findIdsByType($type, $exclude = null, $filter = null)
    {
        if ($filter && !is_callable($filter)) {
            throw new InvalidInstanceError('filter', $filter, 'Closure');
        }

        $conditions = [DB::prepare('is_archived = ? AND is_trashed = ?', false, false)];

        if ($type) {
            $conditions[] = DB::prepare('type IN (?)', (array) $type);
        }

        if (is_callable($filter)) {
            $fields = ['id', 'type', 'raw_additional_properties'];
        } else {
            $fields = ['id'];
        }

        if ($exclude instanceof User) {
            $exclude_ids = [$exclude->getId()];
        } elseif (is_array($exclude)) {
            $exclude_ids = $exclude;
        } elseif ($exclude) {
            $exclude_ids = (array) $exclude;
        } else {
            $exclude_ids = null;
        }

        if ($exclude_ids) {
            $conditions[] = DB::prepare('(id NOT IN (?))', $exclude_ids);
        }

        if ($rows = DB::execute('SELECT ' . implode(', ', $fields) . ' FROM users WHERE ' . implode(' AND ', $conditions))) {
            $result = [];

            foreach ($rows as $row) {
                $user_id = (int) $row['id'];

                if ($filter) {
                    $additional_properties = $row['raw_additional_properties'] ? unserialize($row['raw_additional_properties']) : null;

                    $custom_permissions = [];
                    if ($additional_properties && isset($additional_properties['custom_permissions']) && !empty($additional_properties['custom_permissions'])) {
                        $custom_permissions = $additional_properties['custom_permissions'];
                    }

                    if ($filter($user_id, $row['type'], $custom_permissions)) {
                        $result[] = $user_id;
                    }
                } else {
                    $result[] = $user_id;
                }
            }

            return $result;
        }

        return null;
    }

    // ---------------------------------------------------
    //  Related user listing tables
    // ---------------------------------------------------

    /**
     * Find and properly load users from user listing tables and return them as flattened array.
     *
     * These tables as user listings associated with particular objects. For example, lists of subscribers, lists of
     * people who should be reminded or notified about something etc.
     *
     * @param  string $external_table
     * @param  string $field_prefix
     * @param  string $filter
     * @param  int    $min_state
     * @return array
     */
    public static function findFlattenFromUserListingTable($external_table, $field_prefix, $filter, $min_state = STATE_ARCHIVED)
    {
        [$users, $anonymous_users] = self::findFromUserListingTable($external_table, $field_prefix, $filter, $min_state);

        $result = [];

        if ($users instanceof DBResult && $users->count()) {
            $result = $users->toArray();

            if (is_foreachable($anonymous_users)) {
                $result = array_merge($result, $anonymous_users);
            }
        } else {
            if (is_foreachable($anonymous_users)) {
                $result = $anonymous_users;
            }
        }

        return count($result) ? $result : null;
    }

    /**
     * Find and properly load users from user listing tables and return them separated by instance type.
     *
     * These tables as user listings associated with particular objects. For example, lists of subscribers, lists of
     * people who should be reminded or notified about something etc.
     *
     * @param  string $external_table
     * @param  string $field_prefix
     * @param  string $filter
     * @param  int    $min_state
     * @return array
     */
    public static function findFromUserListingTable($external_table, $field_prefix, $filter, $min_state = STATE_ARCHIVED)
    {
        $loaded_user_ids = [];

        /** @var User[] $loaded_users */
        if ($loaded_users = self::findOnlyUsersFromUserListingTable($external_table, $field_prefix, $filter, $min_state)) {
            foreach ($loaded_users as $loaded_user) {
                $loaded_user_ids[] = $loaded_user->getId();
            }
        }

        $anonymous_users = [];

        $user_name_field = "{$field_prefix}_name";
        $user_email_field = "{$field_prefix}_email";

        if (count($loaded_user_ids)) {
            $where_part = $filter ? DB::prepare("WHERE {$field_prefix}_id NOT IN (?) AND $filter", $loaded_user_ids) : '';
        } else {
            $where_part = $filter ? "WHERE $filter" : '';
        }

        $rows = DB::execute("SELECT DISTINCT $user_name_field, $user_email_field FROM $external_table $where_part ORDER BY $user_name_field, $user_email_field");
        if ($rows) {
            foreach ($rows as $row) {
                if ($row[$user_email_field]) {
                    $anonymous_users[$row[$user_email_field]] = new AnonymousUser($row[$user_name_field], $row[$user_email_field]);
                }
            }
        }

        return [$loaded_users, $anonymous_users];
    }

    /**
     * Return only users from users listing table.
     *
     * User listing tables as user listings associated with particular objects. For example, lists of subscribers, lists
     * of people who should be reminded or notified about something etc.
     *
     * @param  string          $external_table
     * @param  string          $field_prefix
     * @param  string          $filter
     * @param  int             $min_state
     * @return DbResult|User[]
     */
    public static function findOnlyUsersFromUserListingTable($external_table, $field_prefix, $filter, $min_state = STATE_ARCHIVED)
    {
        $user_id_field = "{$field_prefix}_id";

        if ($min_state == STATE_VISIBLE) {
            $state_filter = DB::prepare('users.is_archived = ? AND users.is_trashed = ?', false, false);
        } elseif ($min_state == STATE_ARCHIVED) {
            $state_filter = DB::prepare('users.is_trashed = ?', false);
        } else {
            $state_filter = '';
        }

        if ($filter && $state_filter) {
            $where_part = " WHERE ($state_filter) AND ($filter)";
        } elseif ($filter) {
            $where_part = " WHERE $filter";
        } elseif ($state_filter) {
            $where_part = " WHERE $state_filter";
        } else {
            $where_part = '';
        }

        return self::findBySQL("SELECT DISTINCT users.* FROM users JOIN $external_table ON users.id = {$external_table}.{$user_id_field} $where_part ORDER BY CONCAT(users.first_name, users.last_name, users.email)", $min_state);
    }

    // ---------------------------------------------------
    //  Feed tokens
    // ---------------------------------------------------

    /**
     * Find and return user by feed token.
     *
     * @param  string    $feed_token
     * @return User|null
     */
    public static function findByFeedToken($feed_token)
    {
        $user_id = self::parseFeedToken($feed_token)[0];

        /** @var User $user */
        if ($user = DataObjectPool::get('User', $user_id)) {
            if ($user->getFeedToken() === $feed_token) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Validate and parse feed token.
     *
     * @param  string $feed_token
     * @return array
     */
    private static function parseFeedToken($feed_token)
    {
        if ($feed_token) {
            $bits = explode('-', $feed_token);

            if (count($bits) == 2 && ctype_digit($bits[0]) && preg_match('/^([a-zA-Z0-9_]{80})$/', $bits[1])) {
                return [(int) $bits[0], $bits[1]];
            }
        }

        throw new InvalidParamError('feed_token', $feed_token, 'Invalid feed token');
    }

    // ---------------------------------------------------
    //  Member ID-s
    // ---------------------------------------------------

    /**
     * Array of preloaded member IDs.
     *
     * @var array
     */
    private static $member_ids = [];

    /**
     * Preload member ID-s from users table.
     *
     * @param string $parent_type
     * @param int[]  $parent_ids
     * @param string $parent_id_field
     */
    public static function preloadMemberIdsByField($parent_type, $parent_ids, $parent_id_field)
    {
        if (empty(self::$member_ids[$parent_type])) {
            self::$member_ids[$parent_type] = !empty($parent_ids)
                ? array_fill_keys($parent_ids, [])
                : [];
        }

        if ($rows = DB::execute("SELECT id, $parent_id_field AS 'parent_id' FROM users WHERE $parent_id_field IN (?) ORDER BY id", $parent_ids)) {
            foreach ($rows as $row) {
                self::$member_ids[$parent_type][$row['parent_id']][] = $row['id'];
            }
        }
    }

    public static function preloadMemberIdsFromConnectionTable(
        string $parent_type,
        ?array $parent_ids,
        string $connection_table,
        string $parent_id_field,
        string $user_id_field = 'user_id',
        bool $include_archived_and_trashed = true,
        string $additional_state_filter = ''
    ): void
    {
        if (empty(self::$member_ids[$parent_type])) {
            self::$member_ids[$parent_type] = !empty($parent_ids)
                ? array_fill_keys($parent_ids, [])
                : [];
        }

        $state_filter = '';

        if (!$include_archived_and_trashed) {
            $state_filter = DB::prepare(' AND u.is_archived = ? AND u.is_trashed = ?', false, false);
        }

        if ($additional_state_filter) {
            $state_filter .= $additional_state_filter;
        }

        if ($rows = DB::execute("SELECT u.id AS 'user_id', c.$parent_id_field AS 'parent_id' FROM users AS u LEFT JOIN $connection_table AS c ON u.id = c.$user_id_field WHERE c.$parent_id_field IN (?) {$state_filter} ORDER BY u.id", $parent_ids)) {
            foreach ($rows as $row) {
                self::$member_ids[$parent_type][$row['parent_id']][] = $row['user_id'];
            }
        }
    }

    /**
     * Return member ID-s for a given parent.
     */
    public static function getMemberIdsFor(IMembers $parent, callable $load_callback, bool $use_cache = true): array
    {
        $parent_type = $parent->getMemberIdsPreloadKey();

        if ($use_cache && isset(self::$member_ids[$parent_type]) && isset(self::$member_ids[$parent_type][$parent->getId()])) {
            return self::$member_ids[$parent_type][$parent->getId()];
        } else {
            return AngieApplication::cache()->getByObject($parent, ['members', 'ids'], function () use (&$load_callback) {
                $user_ids = call_user_func($load_callback);

                return $user_ids && is_foreachable($user_ids) ? $user_ids : [];
            }, empty($use_cache));
        }
    }

    public static function getAdminIdsForGroupConversation(
        GroupConversation $conversation,
        callable $load_callback,
        bool $use_cache = true
    ): array
    {
        $parent_type = get_class($conversation);

        if ($use_cache && isset(self::$member_ids[$parent_type]) && isset(self::$member_ids[$parent_type][$conversation->getId()])) {
            return self::$member_ids[$parent_type][$conversation->getId()];
        } else {
            return AngieApplication::cache()->getByObject($conversation, 'admin_ids', function () use (&$load_callback) {
                $user_ids = call_user_func($load_callback);

                return $user_ids && is_foreachable($user_ids) ? $user_ids : [];
            }, empty($use_cache));
        }
    }

    // ---------------------------------------------------
    //  Email Management
    // ---------------------------------------------------

    /**
     * Return user by email address.
     *
     * @param  string                   $email
     * @param  bool                     $extended
     * @return DbResult|DataObject|User
     */
    public static function findByEmail($email, $extended = false)
    {
        if ($email && is_valid_email($email)) {
            if ($extended) {
                $user = self::findOne(
                    [
                        'conditions' => ['email = ?', $email],
                    ]
                );

                if ($user instanceof User) {
                    return $user;
                }

                return self::findOneBySql('SELECT users.* FROM users LEFT JOIN user_addresses ON users.id = user_addresses.user_id WHERE user_addresses.email = ?', $email);
            } else {
                return self::findOne(
                    [
                        'conditions' => ['email = ?', $email],
                    ]
                );
            }
        } else {
            throw new InvalidParamError('email', $email, 'Invalid email address');
        }
    }

    /**
     * Return a list of users by the RFC 822.
     *
     * @param  string         $addresses
     * @param  bool           $load_anonymous
     * @return User[]|IUser[]
     */
    public static function findByAddressList($addresses, $load_anonymous = true)
    {
        require_once ANGIE_PATH . '/classes/RFC822.php';

        $result = [];

        $parsed_addresses = RFC822::parseAddressList($addresses, 'localhost', true, false);

        if ($parsed_addresses && is_foreachable($parsed_addresses)) {
            foreach ($parsed_addresses as $address_or_a_group) {
                if (isset($address_or_a_group->addresses)) {
                    foreach ($address_or_a_group->addresses as $address) {
                        self::parsedAddressToUser($address, $load_anonymous, $result);
                    }
                } else {
                    self::parsedAddressToUser($address_or_a_group, $load_anonymous, $result);
                }
            }
        }

        return $result;
    }

    /**
     * Get user instance from parsed address.
     *
     * @param StdClass $parsed_address
     * @param bool     $load_anonymous
     */
    private static function parsedAddressToUser($parsed_address, $load_anonymous, array &$result)
    {
        if (!empty($parsed_address->mailbox) && !empty($parsed_address->host)) {
            $email = $parsed_address->mailbox . '@' . $parsed_address->host;

            $user = static::findByEmail($email, true);

            if ($user instanceof User) {
                $result[] = $user;
            } elseif ($load_anonymous) {
                $result[] = new AnonymousUser($parsed_address->personal, $email);
            }
        }
    }

    /**
     * Returns true if $address is used by any trashed, archived or visible user.
     *
     * @param  string $address
     * @param  mixed  $exclude_user
     * @return bool
     */
    public static function isEmailAddressInUse($address, $exclude_user = null)
    {
        $exclude_user_id = $exclude_user instanceof User ? $exclude_user->getId() : (int) $exclude_user;

        if ($exclude_user_id) {
            $user_id = (int) DB::executeFirstCell('SELECT id FROM users WHERE id != ? AND email = ?', $exclude_user_id, $address);
        } else {
            $user_id = (int) DB::executeFirstCell('SELECT id FROM users WHERE email = ?', $address);
        }

        if (empty($user_id)) {
            if ($exclude_user_id) {
                $user_id = (int) DB::executeFirstCell('SELECT users.id FROM users, user_addresses WHERE users.id = user_addresses.user_id AND users.id != ? AND user_addresses.email = ?', $exclude_user_id, $address);
            } else {
                $user_id = (int) DB::executeFirstCell('SELECT users.id FROM users, user_addresses WHERE users.id = user_addresses.user_id AND user_addresses.email = ?', $address);
            }
        }

        return (bool) $user_id;
    }

    /**
     * @var array
     */
    private static $additional_addresses_by_user = [];

    /**
     * @param int[] $user_ids
     */
    public static function preloadAdditionalEmailAddresses($user_ids)
    {
        self::$additional_addresses_by_user = !empty($user_ids)
            ? array_fill_keys($user_ids, [])
            : [];

        if ($rows = DB::execute('SELECT user_id, email FROM user_addresses WHERE user_id IN (?) ORDER BY email', $user_ids)) {
            foreach ($rows as $row) {
                self::$additional_addresses_by_user[$row['user_id']][] = $row['email'];
            }
        }
    }

    /**
     * Return array of additional email addresses.
     *
     * @return array|null
     */
    public static function getAdditionalEmailAddressesByUser(User $user)
    {
        if (isset(self::$additional_addresses_by_user[$user->getId()])) {
            return self::$additional_addresses_by_user[$user->getId()];
        } else {
            return DB::executeFirstColumn('SELECT email FROM user_addresses WHERE user_id = ? ORDER BY email', $user->getId());
        }
    }

    /**
     * @var array
     */
    private static $last_login_on_by_user = [];

    public static function preloadLastLoginOn(array $user_ids)
    {
        self::$last_login_on_by_user = !empty($user_ids)
            ? array_fill_keys($user_ids, null)
            : [];

        if ($rows = DB::execute('SELECT id, last_login_on FROM users WHERE id IN (?) AND last_login_on IS NOT NULL', $user_ids)) {
            $rows->setCasting(['last_login_on' => DBResult::CAST_DATETIME]);

            foreach ($rows as $row) {
                self::$last_login_on_by_user[$row['id']] = $row['last_login_on'];
            }
        }
    }

    /**
     * Return last login on for user.
     *
     * @return DateTimeValue
     */
    public static function getLastLoginOnForUser(User $user)
    {
        if (array_key_exists($user->getId(), self::$last_login_on_by_user)) {
            return self::$last_login_on_by_user[$user->getId()];
        } else {
            $row = DB::executeFirstRow('SELECT last_login_on FROM users WHERE id = ?', $user->getId());

            return !empty($row['last_login_on']) ? new DateTimeValue($row['last_login_on']) : null;
        }
    }

    /**
     * Reset manager state (between tests for example).
     */
    public static function resetState()
    {
        self::$additional_addresses_by_user = [];
        self::$member_ids = [];
        self::$last_login_on_by_user = [];
    }

    /**
     * Check if email is example.
     *
     * @param $email
     * @return bool
     */
    public static function isExampleEmail($email)
    {
        return strpos($email, '@example.com') !== false;
    }

    public static function getIdsWhoCanSeeUser(IUser $user): array
    {
        $owner_ids = Users::findOwnerIds();
        $user_company_ids = DB::executeFirstColumn(
            'SELECT id FROM users WHERE company_id = ? AND is_archived = ? AND is_trashed = ?',
            $user->getCompanyId(),
            false,
            false
        );

        // owners and people from user's company can see the user
        $user_ids = array_unique(
            array_merge(
                $owner_ids ?? [],
                $user_company_ids ?? []
            )
        );

        // people who are on the projects with the user
        $project_user_ids = [];
        if ($project_ids = DB::executeFirstColumn('SELECT DISTINCT project_id FROM project_users WHERE user_id = ?', $user->getId())) {
            $project_user_ids = DB::executeFirstColumn(
                'SELECT u.id FROM users u
                    JOIN project_users pu ON u.id = pu.user_id
                     WHERE u.id NOT IN (?) AND pu.project_id IN (?) AND u.is_archived = ? AND u.is_trashed = ?',
                $user_ids,
                $project_ids,
                false,
                false
            );
        }

        $user_ids = array_merge(
            $user_ids,
            $project_user_ids ?? []
        );

        // remove id of $user param
        if (($key = array_search($user->getId(), $user_ids)) !== false) {
            unset($user_ids[$key]);
        }

        return $user_ids ?? [];
    }
}
