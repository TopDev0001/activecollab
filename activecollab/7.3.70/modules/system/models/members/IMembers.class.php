<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Members interface (replacement for users context).
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
interface IMembers
{
    /**
     * Return true if $user is member of this users context.
     *
     * @param  bool $use_cache
     * @return bool
     */
    public function isMember(User $user, $use_cache = true);

    /**
     * Return users in given context.
     *
     * @return User[]|null
     */
    public function getMembers();

    /**
     * Return a list of active members.
     *
     * @return User[]|null
     */
    public function getActiveMembers();

    /**
     * Return a list of archived members.
     *
     * @return User[]|null
     */
    public function getArchivedMembers();

    /**
     * Return a list of trashed members.
     *
     * @return User[]|null
     */
    public function getTrashedMembers();

    public function getMemberIds(bool $use_cache = true): array;

    public function getMemberIdsPreloadKey(): string;

    /**
     * Count users.
     *
     * @param  bool $use_cache
     * @return int
     */
    public function countMembers($use_cache = true);

    /**
     * Replace current set of members with the new set.
     *
     * @param User[]|DBResult $users
     */
    public function setMembers(iterable $users, array $additional = null): void;

    /**
     * Add user to this context.
     *
     * @param User[]|DBResult $users
     * @param array|null      $additional
     */
    public function addMembers($users, $additional = null);

    /**
     * Remove user from this context.
     *
     * @param User[]|DBResult $users
     * @param array|null      $additional
     */
    public function removeMembers($users, $additional = null);

    /**
     * Clear all relations.
     *
     * @param array|null $additional
     */
    public function clearMembers($additional = null);

    /**
     * Clone members from parent context to $to.
     *
     * @param ApplicationObject|IMembers $to
     * @param array|null                 $additional
     */
    public function cloneMembers(IMembers $to, $additional = null);

    /**
     * Replace one user with another user.
     *
     * @param array|null $additional
     */
    public function replaceMember(User $replace, User $with, $additional = null);

    /**
     * Try to add members from a given array (usually from POST or PUT calls).
     *
     * @param  array             $input
     * @param  string            $field_name
     * @param  mixed             $additional
     * @throws InvalidParamError
     */
    public function tryToAddMembersFrom(&$input, $field_name = 'members', $additional = null);

    /**
     * Try to set members from a given array (usually from POST or PUT calls).
     *
     * @param  array             $input
     * @param  string            $field_name
     * @throws InvalidParamError
     */
    public function tryToSetMembersFrom(&$input, $field_name = 'members');
}
