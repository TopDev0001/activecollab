<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Basic members trait, used by IMembersImplementation and IMembersViaConnectionTableImplementation.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
trait IBasicMembersImplementation
{
    /**
     * Return true if $user is member of this users context.
     *
     * @param  bool $use_cache
     * @return bool
     */
    public function isMember(User $user, $use_cache = true)
    {
        return $user->isLoaded() && in_array($user->getId(), $this->getMemberIds($use_cache));
    }

    /**
     * Return users in given context.
     *
     * @return DBResult|User[]|null
     */
    public function getMembers()
    {
        $user_ids = $this->getMemberIds();

        return count($user_ids) ? Users::find(['conditions' => ['id IN (?)', $user_ids]]) : null;
    }

    /**
     * Return a list of active members.
     *
     * @return DBResult|User[]|null
     */
    public function getActiveMembers()
    {
        $user_ids = $this->getMemberIds();

        return count($user_ids) ? Users::find(['conditions' => ['id IN (?) AND is_archived = ? AND is_trashed = ?', $user_ids, false, false]]) : null;
    }

    /**
     * Return a list of archived members.
     *
     * @return DBResult|User[]|null
     */
    public function getArchivedMembers()
    {
        $user_ids = $this->getMemberIds();

        return count($user_ids) ? Users::find(['conditions' => ['id IN (?) AND is_archived = ? AND is_trashed = ?', $user_ids, true, false]]) : null;
    }

    /**
     * Return a list of trashed members.
     *
     * @return DBResult|User[]|null
     */
    public function getTrashedMembers()
    {
        $user_ids = $this->getMemberIds();

        return count($user_ids) ? Users::find(['conditions' => ['id IN (?) AND is_trashed = ?', $user_ids, true]]) : null;
    }

    /**
     * Count users.
     *
     * @param  bool $use_cache
     * @return int
     */
    public function countMembers($use_cache = true)
    {
        return count($this->getMemberIds($use_cache));
    }

    /**
     * Replace one user with another user.
     *
     * @param array|null $additional
     */
    public function replaceMember(User $replace, User $with, $additional = null)
    {
        DB::transact(function () use ($replace, $with, $additional) {
            $this->removeMembers([$replace], $additional);
            $this->addMembers([$with], $additional);
        });

        AngieApplication::cache()->removeByObject($this, ['members']);
    }

    /**
     * Describe members.
     */
    public function describeMembers(array &$result)
    {
        $result['members'] = $this->getMemberIds();
    }

    /**
     * Try to add members from a given array (usually from POST or PUT calls).
     *
     * @param  array             $input
     * @param  string            $field_name
     * @param  mixed             $additional
     * @throws InvalidParamError
     */
    public function tryToAddMembersFrom(&$input, $field_name = 'members', $additional = null)
    {
        if (isset($input[$field_name]) && is_array($input[$field_name])) {
            if (count($input[$field_name]) == 0 || is_array_of_instances($input[$field_name], 'User')) {
                $this->addMembers($input[$field_name]);
            } elseif (is_array_of_ids($input[$field_name])) {
                $this->addMembers(Users::findByIds($input[$field_name]), $additional);
            } else {
                throw new InvalidParamError('[' . $field_name . ']', $input[$field_name], 'Expected a list of IDs or user instances');
            }
        }
    }

    /**
     * Try to set members from a given array (usually from POST or PUT calls).
     *
     * @param  array             $input
     * @param  string            $field_name
     * @throws InvalidParamError
     */
    public function tryToSetMembersFrom(&$input, $field_name = 'members')
    {
        if (isset($input[$field_name]) && is_array($input[$field_name]) && count($input[$field_name])) {
            if (is_array_of_instances($input[$field_name], 'User')) {
                $this->setMembers($input[$field_name]);
            } elseif (is_array_of_ids($input[$field_name])) {
                $this->setMembers(Users::findByIds($input[$field_name]));
            } else {
                throw new InvalidParamError('[' . $field_name . ']', $input[$field_name], 'Expected a list of IDs or user instances');
            }
        }
    }

    public function getMemberIdsPreloadKey(): string
    {
        return get_class($this);
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    abstract public function getMemberIds(bool $use_cache = true): array;

    /**
     * Replace current set of members with the new set.
     *
     * @param User[]|DBResult $users
     */
    abstract public function setMembers(iterable $users, array $additional = null): void;

    /**
     * Add user to this context.
     *
     * @param User[]|DBResult $users
     * @param array|null      $additional
     */
    abstract public function addMembers($users, $additional = null);

    /**
     * Remove user from this context (by moving them to trash).
     *
     * @param User[]|DBResult $users
     * @param array|null      $additional
     */
    abstract public function removeMembers($users, $additional = null);
}
