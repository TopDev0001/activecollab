<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\CommentEvents\CommentCreatedEvent;

trait ICommentsImplementation
{
    public function registerICommentsImplementation()
    {
        $this->registerEventHandler('on_json_serialize', function (array & $result) {
            $result['comments_count'] = $this->countComments();
        });

        $this->registerEventHandler('on_describe_single', function (array & $result) {
            $result['comments'] = Comments::prepareCollection('comments_for_' . get_class($this) . '-' . $this->getId() . '_page_1', null);

            if ($result['comments']->countIds() < 1) {
                $result['comments'] = [];
            }
        });

        $this->registerEventHandler('on_before_move_to_trash', function ($by, $bulk) {
            $parent_conditions = Comments::parentToCondition($this);

            DB::execute("UPDATE comments SET original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE $parent_conditions AND is_trashed = ?", true, true); // Remember original is_trashed flag for already comments subtask
            DB::execute("UPDATE comments SET is_trashed = ?, trashed_on = ?, trashed_by_id = ?, original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE $parent_conditions AND is_trashed = ?", true, DateTimeValue::now(), ($by instanceof User ? $by->getId() : AngieApplication::authentication()->getLoggedUserId()), false, false);  // Trash comments that are not already in trash

            Comments::clearCache();
        });

        $this->registerEventHandler('on_before_restore_from_trash', function ($bulk) {
            $parent_conditions = Comments::parentToCondition($this);

            DB::execute("UPDATE comments SET is_trashed = ?, trashed_on = NULL, trashed_by_id = ?, updated_on = UTC_TIMESTAMP() WHERE $parent_conditions AND original_is_trashed = ?", false, 0, false);
            DB::execute("UPDATE comments SET is_trashed = ?, original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE $parent_conditions AND is_trashed = ?", true, false, true);

            Comments::clearCache();
        });

        $this->registerEventHandler('on_before_delete', function () {
            if ($comment_ids = DB::execute('SELECT id FROM comments WHERE parent_type = ? AND parent_id = ?', get_class($this), $this->getId())) {
                try {
                    DB::beginWork('Droping comments @ ' . __CLASS__);

                    DB::execute('DELETE FROM comments WHERE id IN (?)', $comment_ids);

                    ActivityLogs::deleteByParents(['Comment' => $comment_ids]);
                    Attachments::deleteByParents(['Comment' => $comment_ids]);
                    ModificationLogs::deleteByParents(['Comment' => $comment_ids]);

                    DB::commit('Comments dropped @ ' . __CLASS__);
                } catch (Exception $e) {
                    DB::rollback('Failed to drop comments @ ' . __CLASS__);
                    throw $e;
                }

                Comments::clearCache();
            }
        });
    }

    /**
     * Return code that will tell the application where to route replies to comments.
     *
     * @return string
     */
    public function getCommentRoutingCode()
    {
        return AngieApplication::cache()->getByObject($this, 'comment_routing_code', function () {
            return strtoupper(str_replace('_', '-', Angie\Inflector::underscore(get_class($this)))) . '/' . $this->getId();
        });
    }

    /**
     * Return comment submitted for this project object.
     *
     * @return DBResult|Comment[]
     */
    public function getComments()
    {
        return Comments::find([
            'conditions' => ['parent_type = ? AND parent_id = ? AND is_trashed = ?', get_class($this), $this->getId(), false],
        ]);
    }

    /**
     * Returns true if parent object is read by the given user.
     *
     * @return bool
     */
    public function isRead(User $by)
    {
        if ($this instanceof IAccessLog) {
            $last_comment = $this->getLastComment();

            if ($last_comment instanceof Comment) {
                if ($last_comment->getCreatedById() == $by->getId()) {
                    return true; // Last comment by this user. No need to proceed
                }

                $last_comment_on = $last_comment->getCreatedOn();
            } else {
                $last_comment_on = $this instanceof ICreatedOn ? $this->getCreatedOn() : null;
            }

            if ($last_comment_on) {
                return AccessLogs::isAccessedSince($this, $by, $last_comment_on);
            }
        }

        return true;
    }

    /**
     * Return $count of latest comments.
     *
     * @param  int                $count
     * @return Comment[]|DBResult
     */
    public function getLatestComments($count = 10)
    {
        return Comments::find([
            'conditions' => ['parent_type = ? AND parent_id = ? AND is_trashed = ?', get_class($this), $this->getId(), false],
            'offset' => 0,
            'limit' => $count,
        ]);
    }

    /**
     * Load more comments.
     *
     * @param  array    $loaded_comment_ids
     * @return DBResult
     */
    public function loadMoreComments($loaded_comment_ids, DateTimeValue $reference)
    {
        return Comments::find([
            'conditions' => ['parent_type = ? AND parent_id = ? AND created_on < ? AND id NOT IN (?) AND is_trashed = ?', get_class($this), $this->getId(), $reference, $loaded_comment_ids, false],
        ]);
    }

    /**
     * Return last comment by user.
     *
     * @return Comment
     */
    public function getLastComment()
    {
        $last_comment_id = AngieApplication::cache()->getByObject($this, 'last_comment_id', function () {
            return DB::executeFirstCell('SELECT id FROM comments WHERE ' . Comments::parentToCondition($this, true) . ' ORDER BY created_on DESC, id DESC LIMIT 0, 1');
        });

        return $last_comment_id ? DataObjectPool::get('Comment', $last_comment_id) : null;
    }

    /**
     * Return number of comments for this particular object.
     *
     * @param  bool $use_cache
     * @return int
     */
    public function countComments($use_cache = true)
    {
        return AngieApplication::cache()->getByObject($this, 'comments_count', function () {
            return Comments::countByParent($this);
        }, !$use_cache);
    }

    /**
     * Return list of users involved in a discussion.
     *
     * @return User[]
     */
    public function getCommenters()
    {
        return Users::findBySQL('SELECT DISTINCT users.* FROM users LEFT JOIN comments ON users.id = comments.created_by_id WHERE ' . Comments::parentToCondition($this) . ' AND comments.is_trashed = ? ORDER BY CONCAT(users.first_name, users.last_name, users.email)', false);
    }

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    /**
     * Quickly create and submit a comment.
     *
     * Additional features:
     *
     * - set_source - Set comment source, default is web
     * - log_creation - TRUE by default
     * - subscribe_author - TRUE by default
     * - subscribe_users - Optional list of user ID-s that need to be subscribed
     * - notify_subscribers - TRUE by default
     * - attach_uploaded_files - array of hash codes from uploaded_files table - used in 'create new comment' incoming mail action
     * - created_on - created on datetime - used import comments from external source (i.e. basecamp)
     *
     * @param  string  $body
     * @param  array   $additional
     * @param  bool    $log_access_for_parent
     * @return Comment
     */
    public function submitComment($body, IUser $by, $additional = null, $log_access_for_parent = false)
    {
        $attributes = is_array($additional) ? $additional : [];

        $attributes['parent_type'] = get_class($this);
        $attributes['parent_id'] = $this->getId();
        $attributes['body'] = $body;
        $attributes['ip_address'] = AngieApplication::getVisitorIp();

        $attributes['created_by_id'] = $by->getId();
        $attributes['created_by_name'] = $by->getName();
        $attributes['created_by_email'] = $by->getEmail();

        $comment = Comments::create($attributes, true, false);

        if ($comment) {
            if ($this instanceof ISubscriptions) {
                $set_subscribers = array_var($additional['comment_attributes'], 'subscribers', null, true);

                if (is_array($set_subscribers)) {
                    $this->setSubscribers($set_subscribers, true);
                }

                if (!(array_key_exists('subscribe_author', $additional) && $additional['subscribe_author'] === false)) {
                    $this->subscribe($by, true);
                }
            }

            // Comment is submitted, notify people (any exception thrown by notifier
            // is not relevant to comment creation process) - except incoming mail
            if ($this instanceof ISubscriptions && array_var($additional, 'notify_subscribers', true)) {
                AngieApplication::notifications()
                    ->notifyAbout('new_comment', $this, $by)
                    ->setComment($comment)
                    ->sendToSubscribers();
            }

            // create access log and clear notifications for parent object
            // used when comment is created via incoming mail
            if ($log_access_for_parent && $this instanceof IAccessLog) {
                AccessLogs::logAccess($this, $by);
            }

            DataObjectPool::announce(new CommentCreatedEvent($comment));
        }

        return $comment;
    }

    /**
     * Process incoming mail and return resulting object (or null if message can't be handled).
     *
     * @param  User[]|IUser[]  $to
     * @param  string          $subject
     * @param  string          $text
     * @return DataObject|null
     */
    public function processIncomingMail(IUser $from, array $to, $subject, $text, array $attachments = null)
    {
        $attributes = [
            'attach_uploaded_files' => $attachments,
            'subscribe_author' => true,
            'comment_attributes' => ['subscribers' => []],
        ];

        if (!empty($to)) {
            foreach ($to as $user_to_subscribe) {
                if ($user_to_subscribe instanceof User && $this->canView($user_to_subscribe)) {
                    $attributes['comment_attributes']['subscribers'][] = $user_to_subscribe->getId();
                } else {
                    if ($user_to_subscribe instanceof AnonymousUser) {
                        $attributes['comment_attributes']['subscribers'][] = [$user_to_subscribe->getName(), $user_to_subscribe->getEmail()];
                    }
                }
            }
        }

        if (empty($attributes['comment_attributes']['subscribers'])) {
            unset($attributes['comment_attributes']);
        }

        $comment = $this->submitComment($text, $from, $attributes, true);

        AngieApplication::cache()->removeByObject($this);

        return $comment;
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Returns true if this object allows anonymous comments.
     *
     * @return bool
     */
    public function allowAnonymousComments()
    {
        return true;
    }

    /**
     * Returns true if $user can post a comment to this object.
     *
     * @return bool
     * @throws InvalidInstanceError
     */
    public function canComment(IUser $user)
    {
        if ($this instanceof ITrash && $this->getIsTrashed()) {
            return false;
        }

        if ($user instanceof User) {
            return $this->canView($user);
        } elseif ($user instanceof AnonymousUser) {
            return $this->allowAnonymousComments();
        } else {
            throw new InvalidInstanceError('user', $user, [User::class, AnonymousUser::class]);
        }
    }

    /**
     * Return true if $user can send comments to the parent object via email.
     *
     * @return bool
     */
    public function canCommentViaEmail(IUser $user)
    {
        if (AngieApplication::isOnDemand()) {
            return true;
        } else {
            return $user instanceof User
                && Integrations::findFirstByType(EmailIntegration::class)->getImapHost();
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
    abstract public function canEdit(User $user): bool;
    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Trigger an internal event.
     *
     * @param string $event
     * @param array  $event_parameters
     */
    abstract protected function triggerEvent($event, $event_parameters = null);

    /**
     * Check if specific field is defined.
     *
     * @param  string $field Field name
     * @return bool
     */
    abstract public function fieldExists($field);
}
