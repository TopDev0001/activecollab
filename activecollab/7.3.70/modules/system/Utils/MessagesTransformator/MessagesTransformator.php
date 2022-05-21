<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\MessagesTransformator;

use ActiveCollab\Module\System\Model\Message\UserMessage;
use ApplicationObject;
use IUser;
use Project;

class MessagesTransformator extends IBodyBaseTransformator implements MessagesTransformatorInterface
{
    public function transform(
        Project $project,
        IUser $user,
        string $transform_to_class,
        UserMessage ...$messages
    ): ?ApplicationObject
    {
        return $this->createObject(
            $project,
            $user,
            $transform_to_class,
            [],
            ...$messages
        );
    }

    protected function createBodyFromObjects(
        array $objects_to_transform,
        string $title = '',
        string $separator = PHP_EOL
    ): string
    {
        return parent::createBodyFromObjects(
            $objects_to_transform,
            lang('Created from a chat conversation:') . MessagesTransformatorInterface::PARAGRAPH_SEPARATOR,
            MessagesTransformatorInterface::PARAGRAPH_SEPARATOR
        );
    }

    protected function prepareName(
        array $objects_to_transform,
        IUser $user,
        string $transform_to_class
    ): string
    {
        $conversation = first($objects_to_transform)->getConversation();

        $name = $conversation->getName() ? $conversation->getName() : $conversation->getDisplayName($user);

        if (mb_strlen($name) > 191) {
            $name = substr($name, 0, 191);
        }

        return lang(
            ':created_object from :created_from',
            [
                'created_object' => $this->getNameFromClass($transform_to_class),
                'created_from' => $name,
            ]
        );
    }
}
