<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchDocument;

use Attachments;
use Comment;
use Comments;
use DataObject;
use DateTimeValue;
use DB;
use DBResult;
use IAttachments;
use IBody;
use IComments;
use ICreatedBy;
use ICreatedOn;
use InvalidArgumentException;
use IProjectElement;

abstract class SearchDocument implements SearchDocumentInterface
{
    /**
     * @var DataObject
     */
    private $producer;

    /**
     * @var string
     */
    private $document_context;

    public function __construct(
        DataObject $producer,
        $document_context
    )
    {
        if (!in_array($document_context, self::CONTEXTS)) {
            throw new InvalidArgumentException('Invalid context.');
        }

        $this->producer = $producer;
        $this->document_context = $document_context;
    }

    public function getDocumentContext()
    {
        return $this->document_context;
    }

    public function getDocumentId()
    {
        return implode(
            '-',
            [
                $this->getProducer()->getModelName(true),
                $this->getProducer()->getId(),
            ]
        );
    }

    public function getDocumentPayload()
    {
        $timestamps = $this->getTimestamps();

        if (count($timestamps) > 1) {
            sort($timestamps);
        }

        return [
            'type' => get_class($this->getProducer()),
            'id' => $this->getProducer()->getId(),
            'project_id' => $this->getProjectId(),
            'timestamps' => $timestamps,
            'created_by_id' => array_values($this->getCreatedById()),
            'assignee_id' => array_values($this->getAssigneeId()),
            'label_id' => array_values($this->getLabelId()),
            'name' => $this->getName(),
            'body' => $this->getBody(),
            'body_extensions' => implode("\n\n", $this->getBodyExtensions()),
            'is_hidden_from_clients' => $this->isHiddenFromClients(),
        ];
    }

    // ---------------------------------------------------
    //  Implementation
    // ---------------------------------------------------

    /**
     * @return DataObject|ICreatedOn|IProjectElement|ICreatedBy|IBody
     */
    protected function getProducer()
    {
        return $this->producer;
    }

    protected function getName()
    {
        return $this->getProducer()->getName();
    }

    protected function getTimestamps()
    {
        $result = [];

        if ($this->getProducer() instanceof ICreatedOn) {
            $created_on = $this->getProducer()->getCreatedOn();

            if ($created_on instanceof DateTimeValue) {
                $result[] = $created_on->toMySQL();
            }
        }

        if ($this->getProducer() instanceof IComments) {
            $result = array_merge(
                $result,
                $this->queryComments()[0]
            );
        }

        return array_unique($result);
    }

    /**
     * Return a list of ID-s of people who created elements of this document (document itself, or its comments, subtasks).
     *
     * @return array
     */
    protected function getCreatedById()
    {
        $result = [];

        if ($this->getProducer() instanceof ICreatedBy) {
            $result[] = $this->getProducer()->getCreatedById();
        }

        if ($this->getProducer() instanceof IComments) {
            $result = array_merge(
                $result,
                $this->queryComments()[1]
            );
        }

        return array_unique($result);
    }

    /**
     * Return a list of users who this document, or one of its elements, is assigned to.
     *
     * @return array
     */
    protected function getAssigneeId()
    {
        return [];
    }

    /**
     * Return a list of label ID-s.
     *
     * @return array
     */
    protected function getLabelId()
    {
        return [];
    }

    protected function getBody()
    {
        $result = '';

        if ($this->getProducer() instanceof IBody) {
            $result = (string) $this->getProducer()->getBody();
        }

        if ($this->getProducer() instanceof IAttachments) {
            if ($attachment_rows = DB::execute(
                'SELECT `name` FROM `attachments` WHERE ' . Attachments::parentToCondition($this->getProducer()))
            ) {
                foreach ($attachment_rows as $attachment_row) {
                    $result .= "\n{$attachment_row['name']}";
                }
            }
        }

        return $result;
    }

    protected function getBodyExtensions()
    {
        $result = [];

        if ($this->getProducer() instanceof IComments) {
            $result = $this->queryComments()[2];
        }

        return $result;
    }

    private $queried_comments = null;

    private function queryComments()
    {
        if ($this->queried_comments === null) {
            $timestamps = [];
            $created_by_ids = [];
            $bodies = [];

            if ($comment_rows = DB::execute(
                'SELECT `id`, `body`, `created_by_id`, `created_on`
                    FROM `comments`
                    WHERE ' . Comments::parentToCondition($this->producer) . ' AND `is_trashed` = ?',
                false
            )) {
                $comment_rows->setCasting('created_on', DBResult::CAST_STRING);

                $comment_ids = [];

                foreach ($comment_rows as $comment_row) {
                    $comment_id = $comment_row['id'];
                    $comment_ids[] = $comment_id;

                    if (!in_array($comment_row['created_on'], $timestamps)) {
                        $timestamps[] = $comment_row['created_on'];
                    }

                    if ($comment_row['created_by_id'] && !in_array($comment_row['created_by_id'], $created_by_ids)) {
                        $created_by_ids[] = $comment_row['created_by_id'];
                    }

                    $bodies[$comment_id] = (string) $comment_row['body'];
                }

                if ($attachment_rows = DB::execute(
                    'SELECT `parent_id`, `name`
                        FROM `attachments`
                        WHERE `parent_type` = ? AND `parent_id` IN (?)',
                    Comment::class,
                    $comment_ids
                )) {
                    foreach ($attachment_rows as $attachment_row) {
                        $comment_id = $attachment_row['parent_id'];

                        if (!empty($bodies[$comment_id])) {
                            $bodies[$comment_id] .= "\n{$attachment_row['name']}";
                        }
                    }
                }
            }

            $this->queried_comments = [
                $timestamps,
                $created_by_ids,
                $bodies,
            ];
        }

        return $this->queried_comments;
    }

    protected function getProjectId()
    {
        return 0;
    }

    protected function isHiddenFromClients()
    {
        return true;
    }
}
