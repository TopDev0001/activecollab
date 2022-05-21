<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use Angie\Notifications\PushNotificationInterface;

class NewCommentNotification extends Notification implements PushNotificationInterface
{
    /**
     * Serialize to JSON.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), ['comment_id' => $this->getCommentId()]);
    }

    protected function getMentionsFromParent(): bool
    {
        return false;
    }

    /**
     * Return comment ID.
     *
     * @return int
     */
    public function getCommentId()
    {
        return $this->getAdditionalProperty('comment_id');
    }

    /**
     * Return parent comment.
     *
     * @return Comment
     */
    public function getComment()
    {
        return DataObjectPool::get(Comment::class, $this->getCommentId());
    }

    /**
     * Set a parent comment.
     *
     * @return NewCommentNotification
     */
    public function &setComment(Comment $comment)
    {
        $this->setAdditionalProperty('comment_id', $comment->getId());

        if (is_foreachable($comment->getNewMentions())) {
            $this->setMentionedUsers($comment->getNewMentions());
        }

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        $result = [
            'comment' => $this->getComment(),
        ];

        if ($channel instanceof EmailNotificationChannel) {
            $parent = $this->getParent();

            $result['total_comments'] = $parent instanceof IComments ? $parent->countComments() : 0;
            $result['latest_comments'] = $parent instanceof IComments ? $parent->getLatestComments(5) : null;
        }

        return $result;
    }

    /**
     * Set update flags for combined object updates collection.
     */
    public function onObjectUpdateFlags(array &$updates)
    {
        $updates['new_comments'][] = $this->getId();
    }

    /**
     * This method is called when we need to load related notification objects for API response.
     */
    public function onRelatedObjectsTypeIdsMap(array &$type_ids_map)
    {
        if (empty($type_ids_map[Comment::class])) {
            $type_ids_map[Comment::class] = [];
        }

        if (!in_array($this->getCommentId(), $type_ids_map[Comment::class])) {
            $type_ids_map[Comment::class][] = $this->getCommentId();
        }

        $parent = $this->getParent();

        if ($parent instanceof IProjectElement) {
            if (empty($type_ids_map[Project::class])) {
                $type_ids_map[Project::class] = [$parent->getProjectId()];
            } else {
                if (!in_array($parent->getProjectId(), $type_ids_map[Project::class])) {
                    $type_ids_map[Project::class][] = $parent->getProjectId();
                }
            }
        }
    }

    public function optOutConfigurationOptions(NotificationChannel $channel = null): array
    {
        if ($channel instanceof EmailNotificationChannel) {
            return array_merge(parent::optOutConfigurationOptions($channel), ['notifications_user_send_email_subscriptions']);
        }

        return parent::optOutConfigurationOptions($channel);
    }
}
