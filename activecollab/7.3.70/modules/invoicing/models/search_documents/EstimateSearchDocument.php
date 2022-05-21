<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchDocument\SearchDocumentInterface;

final class EstimateSearchDocument extends BaseInvoiceSearchDocument
{
    public function __construct(Estimate $producer)
    {
        parent::__construct($producer, SearchDocumentInterface::CONTEXT_ESTIMATES);
    }
}
