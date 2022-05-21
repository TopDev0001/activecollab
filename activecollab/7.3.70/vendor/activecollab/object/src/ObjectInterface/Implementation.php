<?php

/*
 * This file is part of the Active Collab Object project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Object\ObjectInterface;

use ActiveCollab\Object\ObjectInterface;

/**
 * @package ActiveCollab\Object\ObjectInterface
 */
trait Implementation
{
    /**
     * {@inheritdoc}
     */
    public function is($object)
    {
        return $object instanceof ObjectInterface &&
            get_class($this) === get_class($object) &&
            $this->getId() === $object->getId();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getId();
}
