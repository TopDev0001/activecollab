<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait IMembersViaConnectionTableImplementation
{
    use IBasicMembersImplementation;

    public function registerIMembersViaConnectionTableImplementation(): void
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $this->describeMembers($result);
            }
        );
        $this->registerEventHandler('on_before_delete', function () {
            $this->clearMembers();
        });
    }

    public function getMemberIds(bool $use_cache = true): array
    {
        return Users::getMemberIdsFor(
            $this,
            function () {
                $conditions = DB::prepare(sprintf('m.%s = ?', $this->getMembersFkName()), $this->getId());

                if (!$this->includeArchivedAndTrashedMembers()) {
                    $conditions .= DB::prepare(' AND u.is_archived = ? AND u.is_trashed = ?', false, false);
                }

                return DB::executeFirstColumn(
                    sprintf(
                        "SELECT u.id AS 'id' FROM users AS u LEFT JOIN %s AS m ON u.id = m.user_id WHERE %s ORDER BY u.id",
                        $this->getMembersTableName(),
                        $conditions
                    )
                );
            },
            $use_cache
        );
    }

    /**
     * Replace current set of members with the new set.
     *
     * @param User[]|DBResult|iterable $users
     */
    public function setMembers(iterable $users, array $additional = null): void
    {
        DB::transact(
            function () use ($users, $additional) {
                $this->untouchable(
                    function () {
                        $this->clearMembers();
                    }
                );

                if ($users && is_foreachable($users)) {
                    $this->addMembers($users, $additional);
                }
            }
        );
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
            DB::transact(
                function () use ($users) {
                    $batch = new DBBatchInsert(
                        $this->getMembersTableName(),
                        [
                            'user_id', $this->getMembersFkName(),
                        ],
                        50,
                        DBBatchInsert::REPLACE_RECORDS
                    );

                    foreach ($users as $user) {
                        if ($user instanceof User) {
                            $batch->insert($user->getId(), $this->getId());
                        } else {
                            throw new InvalidParamError('users', $users);
                        }
                    }

                    $batch->done();
                }
            );

            $this->touch();
        }
    }

    /**
     * Remove user from this context.
     *
     * @param  User[]|DBResult   $users
     * @param  array|null        $additional
     * @throws InvalidParamError
     */
    public function removeMembers($users, $additional = null)
    {
        if (count($users)) {
            $members_table = $this->getMembersTableName();
            $members_fk = $this->getMembersFkName();

            $user_ids = [];

            foreach ($users as $user) {
                if ($user instanceof User) {
                    $user_ids[] = $user->getId();
                } else {
                    throw new InvalidParamError('users', $users);
                }
            }

            DB::execute("DELETE FROM $members_table WHERE $members_fk = ? AND user_id IN (?)", $this->getId(), $user_ids);

            $this->touch();
        }
    }

    /**
     * Clone members from parent context to $to.
     *
     * @param ApplicationObject|IMembers $to
     * @param array|null                 $additional
     */
    public function cloneMembers(IMembers $to, $additional = null)
    {
        DB::transact(function () use ($to) {
            $batch = new DBBatchInsert($this->getMembersTableName(), [$this->getMembersFkName(), 'user_id'], 50, DBBatchInsert::REPLACE_RECORDS);

            foreach ($this->getMemberIds() as $member_id) {
                $batch->insert($to->getId(), $member_id);
            }

            $batch->done();

            $to->touch();
        }, 'Cloning project members');
    }

    /**
     * Clear all relations.
     *
     * @param array|null $additional
     */
    public function clearMembers($additional = null)
    {
        $members_table = $this->getMembersTableName();
        $members_fk = $this->getMembersFkName();

        DB::execute("DELETE FROM $members_table WHERE $members_fk = ?", $this->getId());

        $this->touch();
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

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    /**
     * Cached members table name.
     *
     * @var string
     */
    private $members_table_name;

    /**
     * Return table name.
     *
     * @return string
     */
    public function getMembersTableName()
    {
        if (empty($this->members_table_name)) {
            $this->members_table_name = $this->getModelName(true, true) . '_users';
        }

        return $this->members_table_name;
    }

    /**
     * Cached members FK name.
     *
     * @var string
     */
    private $members_fk_name;

    /**
     * Return name of the FK in the connection table.
     *
     * @return string
     */
    public function getMembersFkName()
    {
        if (empty($this->members_fk_name)) {
            $this->members_fk_name = $this->getModelName(true, true) . '_id';
        }

        return $this->members_fk_name;
    }

    /**
     * Should we include or ignore archived and trashed members (TRUE for include, FALSE for ignore).
     *
     * @return bool
     */
    protected function includeArchivedAndTrashedMembers()
    {
        return true;
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    abstract public function getId();
    abstract public function getModelName(
        bool $underscore = false,
        bool $singular = false
    ): string;

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Refresh object's updated_on flag.
     *
     * @param User|null  $by
     * @param array|null $additional
     * @param bool       $save
     */
    abstract public function touch($by = null, $additional = null, $save = true);

    /**
     * Run $callback while this object is untouchable.
     */
    abstract public function untouchable(callable $callback);
}
