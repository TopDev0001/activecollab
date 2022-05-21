<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ValueContainer\Request;

use LogicException;
use Psr\Http\Message\ServerRequestInterface;

class RequestValueContainer implements RequestValueContainerInterface
{
    private ?ServerRequestInterface $request = null;
    private string $attribute_name;

    public function __construct(string $attribute_name)
    {
        $this->attribute_name = $attribute_name;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function setRequest(?ServerRequestInterface $request): RequestValueContainerInterface
    {
        $this->request = $request;

        return $this;
    }

    public function hasValue(): bool
    {
        if (!$this->getRequest()) {
            throw new LogicException('Request not set.');
        }

        return array_key_exists($this->attribute_name, $this->getRequest()->getAttributes());
    }

    public function getValue()
    {
        if (!$this->getRequest()) {
            throw new LogicException('Request not set.');
        }

        return $this->request->getAttribute($this->attribute_name);
    }
}
