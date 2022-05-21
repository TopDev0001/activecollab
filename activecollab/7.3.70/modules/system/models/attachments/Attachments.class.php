<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Model\Message\UserMessage;

/**
 * Application level attachments class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class Attachments extends FwAttachments
{
    /**
     * Return new collection.
     *
     * @param  User|null         $user
     * @return ModelCollection
     * @throws InvalidParamError
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'attachments_in_project')) {
            $bits = explode('_', $collection_name);
            $project_id = array_pop($bits);
        } else {
            $project_id = null;
        }

        $project = DataObjectPool::get(Project::class, $project_id);

        if ($project instanceof Project) {
            $collection = parent::prepareCollection($collection_name, $user);

            $project->getTypeIdsMapOfPotentialAttachmentParents();

            return $collection;
        } else {
            throw new InvalidParamError('collection_name', $collection_name, 'Invalid collection name');
        }
    }

    public static function prepareCursorCollection(string $collection_name, User $user): CursorModelCollection
    {
        $collection = parent::prepareCursorCollection($collection_name, $user);

        if (isset($_GET['cursor'])) {
            $collection->setCursor((int) $_GET['cursor']);
        }

        if (isset($_GET['limit'])) {
            $collection->setLimit((int) $_GET['limit']);
        } else {
            $collection->setLimit(50);
        }

        if (str_starts_with($collection_name, 'attachments_in_conversation')) {
            $bits = explode('_', $collection_name);

            $conversation = DataObjectPool::get(Conversation::class, (int) array_pop($bits));

            if (!empty($conversation) && $conversation->canView($user)) {
                $message_ids = DB::executeFirstColumn(
                    'SELECT id FROM messages WHERE type = ? AND conversation_id = ?',
                    UserMessage::class,
                    $conversation->getId()
                ) ?? [0];

                $collection->setConditions(
                    'project_id = ? AND parent_type = ? AND parent_id IN (?)',
                    0,
                    UserMessage::class,
                    $message_ids
                );

                return $collection;
            } else {
                throw new ImpossibleCollectionError('Conversation not found or user cannot see it.');
            }
        } else {
            throw new InvalidParamError(
                'collection_name',
                $collection_name,
                'Invalid collection name'
            );
        }
    }
}
