<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\History\Renderers\IsHiddenFromClientsHistoryFieldRenderer;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use Angie\Search\SearchDocument\SearchDocumentInterface;

abstract class File extends BaseFile implements RoutingContextInterface
{
    use RoutingContextImplementation;

    public function getRoutingContext(): string
    {
        return 'file';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'project_id' => $this->getProjectId(),
            'file_id' => $this->getId(),
        ];
    }

    /**
     * Create a copy of this object and optionally save it.
     *
     * @param  bool $save
     * @return File
     */
    public function copy($save = false)
    {
        /** @var File $copy */
        $copy = parent::copy(false);

        if (is_file($this->getPath())) {
            $copy->setLocation(AngieApplication::storeFile($this->getPath())[1]);
        }

        if ($save) {
            $copy->save();
        }

        return $copy;
    }

    public function getHistoryFieldRenderers(): array
    {
        $renderers = parent::getHistoryFieldRenderers();

        $renderers['is_hidden_from_clients'] = new IsHiddenFromClientsHistoryFieldRenderer();

        return $renderers;
    }

    public function getSearchDocument(): SearchDocumentInterface
    {
        return new ProjectElementSearchDocument($this);
    }

    public function save()
    {
        if ($this->isNew()) {
            $this->setName(Files::getProjectSafeName($this->getName(), $this->getProject()));
        }

        parent::save();
    }
}
