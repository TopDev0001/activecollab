<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class CommentCreatedActivityLog extends InstanceCreatedActivityLog
{
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'comment_id' => $this->getAdditionalProperty('comment_id'),
            ]
        );
    }

    /**
     * Return comment instance.
     *
     * @return Comment
     */
    public function getComment()
    {
        return DataObjectPool::get(Comment::class, $this->getCommentId());
    }

    public function setComment(Comment $comment)
    {
        $this->setCreatedOn($comment->getCreatedOn());
        $this->setCreatedBy($comment->getCreatedBy());

        $this->setAdditionalProperty('comment_id', $comment->getId());
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
     * This method is called when we need to load related notification objects for API response.
     */
    public function onRelatedObjectsTypeIdsMap(array & $type_ids_map)
    {
        parent::onRelatedObjectsTypeIdsMap($type_ids_map);

        if (empty($type_ids_map[Comment::class])) {
            $type_ids_map[Comment::class] = [];
        }

        if (!in_array($this->getAdditionalProperty('comment_id'), $type_ids_map[Comment::class])) {
            $type_ids_map[Comment::class][] = $this->getAdditionalProperty('comment_id');
        }
    }
}
