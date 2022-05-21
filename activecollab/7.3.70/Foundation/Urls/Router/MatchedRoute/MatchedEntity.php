<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\MatchedRoute;

class MatchedEntity extends MatchedRoute implements MatchedEntityInterface
{
    private $entity_name;
    private $entity_id;

    public function __construct(string $route_name, array $url_params, string $entity_name, int $entity_id)
    {
        parent::__construct($route_name, $url_params);

        $this->entity_name = $entity_name;
        $this->entity_id = $entity_id;
    }

    public function getEntityName(): string
    {
        return $this->entity_name;
    }

    public function getEntityId(): int
    {
        return $this->entity_id;
    }
}
