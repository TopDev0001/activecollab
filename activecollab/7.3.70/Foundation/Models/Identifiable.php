<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Models;

class Identifiable implements IdentifiableInterface
{
    private $type;
    private $id;

    public function __construct(string $type, int $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getId()
    {
        return $this->id;
    }
}
