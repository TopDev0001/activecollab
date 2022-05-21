<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ReactionEvents\ReactionCreatedEvent;

trait IReactionsImplementation
{
    public function registerIReactionsImplementation(): void
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $result['reactions'] = Reactions::getDetailsByParent($this);
            }
        );

        $this->registerEventHandler(
            'on_before_delete',
            function () {
                $reaction_ids = DB::execute(
                    'SELECT id FROM reactions WHERE parent_type = ? AND parent_id = ?',
                    get_class($this),
                    $this->getId()
                );

                if ($reaction_ids) {
                    try {
                        DB::beginWork('Droping reactions @ ' . __CLASS__);

                        DB::execute('DELETE FROM reactions WHERE id IN (?)', $reaction_ids);

                        DB::commit('Reactions dropped @ ' . __CLASS__);
                    } catch (Exception $e) {
                        DB::rollback('Failed to drop reactions @ ' . __CLASS__);

                        throw $e;
                    }

                    Reactions::clearCache();
                }
            }
        );
    }

    /**
     * Return reaction submitted for this project object.
     *
     * @return DBResult|Reaction[]
     */
    public function getReactions()
    {
        return Reactions::find([
            'conditions' => ['parent_type = ? AND parent_id = ?', get_class($this), $this->getId()],
        ]);
    }

    /**
     * Return existing reaction by user.
     *
     * @param  string              $type
     * @param  int                 $created_by_id
     * @return DataObject|Reaction
     */
    public function getExistingReactionByUser($type, $created_by_id)
    {
        return Reactions::findOneBy(
            [
                'parent_type' => get_class($this),
                'parent_id' => $this->getId(),
                'type' => $type,
                'created_by_id' => $created_by_id,
            ]
        );
    }

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    public function submitReaction(IUser $by, array $additional = []): Reaction
    {
        $reaction = Reactions::create(
            array_merge(
                [
                    'parent_type' => get_class($this),
                    'parent_id' => $this->getId(),
                    'created_by_id' => $by->getId(),
                    'created_by_name' => $by->getDisplayName(),
                    'created_by_email' => $by->getEmail(),
                ],
                $additional
            )
        );

        DataObjectPool::announce(new ReactionCreatedEvent($reaction));

        return $reaction;
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Returns true if this object allows anonymous reactions.
     *
     * @return bool
     */
    public function allowAnonymousReactions()
    {
        return true;
    }

    /**
     * Returns true if $user can post a reaction to this object.
     *
     * @return bool
     * @throws InvalidInstanceError
     */
    public function canReact(IUser $user)
    {
        if ($this instanceof ITrash && $this->getIsTrashed()) {
            return false;
        }

        if ($user instanceof User) {
            return $this->canView($user);
        } elseif ($user instanceof AnonymousUser) {
            return $this->allowAnonymousReactions();
        } else {
            throw new InvalidInstanceError('user', $user, [User::class, AnonymousUser::class]);
        }
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return object ID.
     *
     * @return int
     */
    abstract public function getId();

    abstract public function canView(User $user): bool;
    abstract protected function registerEventHandler(string $event, callable $handler): void;
}
