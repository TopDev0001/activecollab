<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Inflector;

trait IMembersImplementation
{
    use IBasicMembersImplementation;

    public function registerIMembersImplementation(): void
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $this->describeMembers($result);
            }
        );
        $this->registerEventHandler(
            'on_before_delete',
            function () {
                if ($members = $this->getMembers()) {
                    foreach ($members as $member) {
                        $member->delete();
                    }
                }
            }
        );
    }

    public function getMemberIds(bool $use_cache = true): array
    {
        return Users::getMemberIdsFor(
            $this,
            function () {
                return DB::executeFirstColumn(
                    sprintf(
                        'SELECT `id` FROM `users` WHERE %s = ? ORDER BY `id`',
                        $this->getMembershipFieldName()
                    ),
                    $this->getId()
                );
            },
            $use_cache
        );
    }

    /**
     * Replace current set of members with the new set.
     *
     * @param  User[]|DBResult     $users
     * @throws NotImplementedError
     */
    public function setMembers(iterable $users, array $additional = null): void
    {
        DB::transact(
            function () use ($users, $additional) {
                $existing_member_ids = $this->getMemberIds();
                $to_add = [];
                $to_keep = [];
                $to_remove = [];

                if (is_iterable($users) && !empty($users)) {
                    foreach ($users as $user) {
                        if (in_array($user->getId(), $existing_member_ids)) {
                            $to_keep[] = $user->getId();
                        } else {
                            $to_add[$user->getId()] = $user;
                        }
                    }
                }

                if (count($to_add)) {
                    $this->untouchable(
                        function () use ($to_add, $additional) {
                            $this->addMembers($to_add, $additional);
                        }
                    );
                }

                foreach ($existing_member_ids as $existing_member_id) {
                    if (isset($to_add[$existing_member_id]) || in_array($existing_member_id, $to_keep)) {
                        continue;
                    }

                    $to_remove[] = $existing_member_id;
                }

                if (count($to_remove)) {
                    $this->removeMembers(Users::findByIds($to_remove), $additional);
                }

                $this->touch();
            }
        );
    }

    /**
     * Add user to this context.
     *
     * @param  User[]|DBResult   $users
     * @param  array|null        $additional
     * @throws InvalidParamError
     */
    public function addMembers($users, $additional = null)
    {
        if (empty($users) || !is_foreachable($users)) {
            throw new InvalidParamError('users', $users, 'Foreachable list of users expected');
        }

        DB::transact(function () use ($users, $additional) {
            $parents_to_update = [];

            $getter_name = $this->getMembershipGetterName();
            $setter_name = $this->getMembershipSetterName();

            foreach ($users as $user) {
                $current_memership_value = $user->$getter_name();

                if ($current_memership_value != $this->getId()) {
                    $user->$setter_name($this->getId());
                    $user->save();

                    if (!in_array($current_memership_value, $parents_to_update)) {
                        $parents_to_update[] = $current_memership_value;
                    }
                }
            }

            if (count($parents_to_update)) {
                $class_name = get_class($this);

                foreach ($parents_to_update as $parent_to_update) {
                    $parent = new $class_name($parent_to_update);

                    if ($parent instanceof ApplicationObject && $parent->isLoaded()) {
                        $parent->touch();
                    }
                }
            }

            if (empty($additional) || empty($additional['dont_touch_this'])) {
                $this->touch();
            }
        }, 'Add members');
    }

    /**
     * Remove user from this context.
     *
     * @param User[]|DBResult $users
     * @param array|null      $additional
     */
    public function removeMembers($users, $additional = null)
    {
        if (count($users)) {
            DB::transact(function () use ($users) {
                foreach ($users as $user) {
                    if ($this->isMember($user)) {
                        $user->moveToTrash();
                    }
                }

                $this->touch();
            }, 'Moving members to trash');
        }
    }

    /**
     * Clear all relations (move them to trash).
     *
     * @param array|null $additional
     */
    public function clearMembers($additional = null)
    {
        DB::transact(function () {
            if ($members = $this->getMembers()) {
                foreach ($members as $member) {
                    $member->moveToTrash();
                }
            }

            $this->touch();
        }, 'Clear members (by moving them to trash)');
    }

    /**
     * Clone members from parent context to $to.
     *
     * @param  ApplicationObject|IMembers $to
     * @param  array|null                 $additional
     * @throws NotImplementedError
     */
    public function cloneMembers(IMembers $to, $additional = null)
    {
        throw new NotImplementedError(__METHOD__, 'Clone method is not available for directly connected members');
    }

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    /**
     * Cached membership field, getter, setter and relation data.
     *
     * @var string
     */
    private $membership_field_name;
    private $membership_getter_name;
    private $membership_setter_name;
    private $membership_parent_method_name;

    /**
     * Return membership field name.
     *
     * @return string
     */
    public function getMembershipFieldName()
    {
        if (empty($this->membership_field_name)) {
            $this->membership_field_name = Inflector::underscore(get_class($this)) . '_id';
        }

        return $this->membership_field_name;
    }

    /**
     * Return membership setter name.
     *
     * @return string
     */
    public function getMembershipGetterName()
    {
        if (empty($this->membership_getter_name)) {
            $this->membership_getter_name = 'get' . Inflector::camelize($this->getMembershipFieldName());
        }

        return $this->membership_getter_name;
    }

    /**
     * Return membership setter name.
     *
     * @return string
     */
    public function getMembershipSetterName()
    {
        if (empty($this->membership_setter_name)) {
            $this->membership_setter_name = 'set' . Inflector::camelize($this->getMembershipFieldName());
        }

        return $this->membership_setter_name;
    }

    /**
     * Return user class name that will get instance of $this class that member belongs to.
     *
     * @return string
     */
    public function getMembershipParentMethodName()
    {
        if (empty($this->membership_parent_method_name)) {
            $this->membership_parent_method_name = 'get' . get_class($this);
        }

        return $this->membership_parent_method_name;
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
     * Return value of specific field and typecast it...
     *
     * @param  string $field   Field value
     * @param  mixed  $default Default value that is returned in case of any error
     * @return mixed
     */
    abstract public function getFieldValue($field, $default = null);

    abstract public function setFieldValue(string $field, $value);

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
