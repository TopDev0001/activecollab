<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchDocument\SearchDocument;

class ProjectElementSearchDocument extends SearchDocument
{
    private $project_id;
    private $is_hidden_from_clients;

    /**
     * ProjectElementSearchDocument constructor.
     *
     * @param IProjectElement|DataObject $project_element
     */
    public function __construct(IProjectElement $project_element)
    {
        parent::__construct($project_element, self::CONTEXT_PROJECTS);

        $this->project_id = $project_element->getProjectId();
        $this->is_hidden_from_clients = $project_element->getIsHiddenFromClients();
    }

    protected function getProjectId()
    {
        return $this->project_id;
    }

    protected function isHiddenFromClients()
    {
        return $this->is_hidden_from_clients;
    }
}
