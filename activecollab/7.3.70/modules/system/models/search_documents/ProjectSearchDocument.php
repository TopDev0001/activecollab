<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchDocument\SearchDocument;

/**
 * @method Project getProducer()
 */
final class ProjectSearchDocument extends SearchDocument
{
    public function __construct(Project $project)
    {
        parent::__construct($project, self::CONTEXT_PROJECTS);
    }

    protected function getBody()
    {
        return (string) $this->getProducer()->getBody();
    }

    protected function getAssigneeId()
    {
        $result = parent::getAssigneeId();

        $leader_id = (int) $this->getProducer()->getLeaderId();

        if ($leader_id && !in_array($leader_id, $result)) {
            $result[] = $leader_id;
        }

        return $result;
    }

    protected function getLabelId()
    {
        $result = parent::getLabelId();

        $label_id = (int) $this->getProducer()->getLabelId();

        if ($label_id && !in_array($label_id, $result)) {
            $result[] = $label_id;
        }

        return $result;
    }

    protected function isHiddenFromClients()
    {
        return false;
    }
}
