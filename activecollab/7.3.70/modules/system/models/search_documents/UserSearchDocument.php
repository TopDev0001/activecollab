<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchDocument\SearchDocument;
use Angie\Search\SearchDocument\SearchDocumentInterface;

/**
 * @method User getProducer()
 */
final class UserSearchDocument extends SearchDocument
{
    public function __construct(User $producer)
    {
        parent::__construct($producer, SearchDocumentInterface::CONTEXT_PEOPLE);
    }

    protected function getName()
    {
        if (empty($this->getProducer()->getFirstName()) && empty($this->getProducer()->getLastName())) {
            return $this->getProducer()->getEmail();
        } else {
            return trim($this->getProducer()->getDisplayName() . ' ' . $this->getProducer()->getEmail());
        }
    }
}
