<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Inflector;
use Angie\Search\SearchBuilder\SearchBuilder;
use Angie\Search\SearchEngineInterface;
use Psr\Log\LoggerInterface;

final class ProjectElementsSearchBuilder extends SearchBuilder implements ProjectElementsSearchBuilderInterface
{
    /**
     * @var string
     */
    private $project_elements;

    /**
     * ProjectElementsSearchBuilder constructor.
     *
     * $project_elements is plural, underscore name of the elements (notes, task_lists etc).
     *
     * @param string $project_elements
     */
    public function __construct(SearchEngineInterface $search_engine, LoggerInterface $logger, $project_elements)
    {
        parent::__construct($search_engine, $logger);

        $this->project_elements = $project_elements;
    }

    public function getName()
    {
        return "Build {$this->project_elements} search index";
    }

    /**
     * @return string
     */
    public function getProjectElements()
    {
        return $this->project_elements;
    }

    protected function getRecordsToAdd()
    {
        $manager = $this->getManagerClassName();

        return call_user_func(
            "$manager::find",
            [
                'conditions' => [
                    'is_trashed' => false,
                ],
                'order' => 'id',
            ]
        );
    }

    private function getManagerClassName()
    {
        return Inflector::camelize($this->project_elements);
    }
}
