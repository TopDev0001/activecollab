<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchDocument\SearchDocument;
use Angie\Search\SearchDocument\SearchDocumentInterface;

/**
 * @method Company getProducer()
 */
final class CompanySearchDocument extends SearchDocument
{
    public function __construct(Company $producer)
    {
        parent::__construct($producer, SearchDocumentInterface::CONTEXT_PEOPLE);
    }

    protected function getBody()
    {
        $bits = [
            parent::getBody(),
        ];

        if (!empty($this->getProducer()->getAddress())) {
            $bits[] = $this->getProducer()->getAddress();
        }

        if (!empty($this->getProducer()->getHomepageUrl())) {
            $bits[] = $this->getProducer()->getHomepageUrl();
        }

        if (!empty($this->getProducer()->getPhone())) {
            $bits[] = $this->getProducer()->getPhone();
        }

        if (!empty($this->getProducer()->getNote())) {
            $bits[] = $this->getProducer()->getNote();
        }

        return implode("\n", $bits);
    }
}
