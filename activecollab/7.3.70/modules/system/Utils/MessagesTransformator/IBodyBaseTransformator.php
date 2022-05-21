<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\MessagesTransformator;

use ApplicationObject;
use DB;
use Discussion;
use IAttachments;
use IBody;
use IUser;
use Note;
use Project;
use Task;

abstract class IBodyBaseTransformator
{
    private $data_manager;

    public function __construct(callable $data_manager)
    {
        $this->data_manager = $data_manager;
    }

    protected function createObject(
        Project $project,
        IUser $user,
        string $object_type,
        array $additional_attributes = [],
        IBody ...$objects_to_transform
    ): ?ApplicationObject
    {
        $data_object = null;
        DB::transact(function () use (
            $object_type,
            $additional_attributes,
            $project,
            $objects_to_transform,
            $user,
            &$data_object
        ) {
            $data_object = call_user_func(
                $this->data_manager,
                $object_type,
                array_merge(
                    $additional_attributes,
                    [
                        'type' => $object_type,
                        'project_id' => $project->getId(),
                        'name' => $this->prepareName($objects_to_transform, $user, $object_type),
                        'body' => $this->createBodyFromObjects($objects_to_transform),
                        'created_by_id' => $user->getId(),
                    ]
                )
            );

            if ($data_object instanceof IAttachments) {
                $this->cloneAttachments(
                    $objects_to_transform,
                    $data_object
                );
            }
        });

        return $data_object;
    }

    protected function cloneAttachments(array $objects_to_transform, IAttachments $data_object): void
    {
        foreach ($objects_to_transform as $object) {
            if ($object instanceof IAttachments && $object->hasAttachments()) {
                $object->cloneAttachmentsTo($data_object);
            }
        }
    }

    protected function prepareName(array $objects_to_transform, IUser $user, string $transform_to_class): string
    {
        $name = [];
        foreach ($objects_to_transform as $object) {
            $name[] = $object->getName();
        }

        return lang(
            ':created_object created from :name by :by',
            [
                'created_object' => $this->getNameFromClass($transform_to_class),
                'created_from' => implode(',', $name),
                'by' => $user->getDisplayName(),
            ]
        );
    }

    protected function getNameFromClass(string $transform_to_class): string
    {
        switch ($transform_to_class) {
            case Note::class:
                return lang('Note');
            case Discussion::class:
                return lang('Discussion');
            case Task::class:
            default:
                return lang('Task');
        }
    }

    protected function createBodyFromObjects(
        array $objects_to_transform,
        string $title = '',
        string $separator = PHP_EOL
    ): string
    {
        $body = [];

        foreach ($objects_to_transform as $object) {
            $body[] = $object->getBody();
        }

        return $title . implode($separator, $body);
    }
}
