<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\ModalArguments;

use ActiveCollab\Foundation\Urls\Router\UrlAssembler\UrlAssemblerInterface;
use Discussion;
use InvalidArgumentException;
use Note;
use Task;

class ModalArguments implements ModalArgumentsInterface
{
    private $url_assembler;
    private $resource_type;
    private $resource_id;
    private $project_id;
    private $note_group_id;

    public function __construct(
        UrlAssemblerInterface $url_assembler,
        string $entity_type,
        int $entity_id,
        int $project_id,
        int $note_group_id = null
    )
    {
        $this->url_assembler = $url_assembler;
        $this->resource_type = $entity_type;
        $this->resource_id = $entity_id;
        $this->project_id = $project_id;
        $this->note_group_id = $note_group_id;
    }

    public function getEntityType(): string
    {
        return $this->resource_type;
    }

    public function getEntityId(): int
    {
        return $this->resource_id;
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }

    public function getNoteGroupId(): ?int
    {
        return $this->note_group_id;
    }

    private $view_url;

    public function getViewUrl(): string
    {
        if (empty($this->view_url)) {
            switch ($this->getEntityType()) {
                case Task::class:
                    $this->view_url = $this->url_assembler->assemble(
                        'task',
                        [
                            'project_id' => $this->getProjectId(),
                            'task_id' => $this->getEntityId(),
                        ]
                    );
                    break;
                case Discussion::class:
                    $this->view_url = $this->url_assembler->assemble(
                        'discussion',
                        [
                            'project_id' => $this->getProjectId(),
                            'discussion_id' => $this->getEntityId(),
                        ]
                    );
                    break;
                case Note::class:
                    $this->view_url = $this->url_assembler->assemble(
                        'note',
                        [
                            'project_id' => $this->getProjectId(),
                            'note_id' => $this->getEntityId(),
                        ]
                    );
                    break;
                default:
                    throw new InvalidArgumentException(sprintf('Unsupported type %s', $this->resource_type));
            }
        }

        return $this->view_url;
    }
}
