<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchDocument\SearchDocumentInterface;

final class InvoiceSearchDocument extends BaseInvoiceSearchDocument
{
    public function __construct(Invoice $producer)
    {
        parent::__construct($producer, SearchDocumentInterface::CONTEXT_INVOICES);
    }

    protected function getBody()
    {
        $body = parent::getBody();

        if (!empty($this->getProducer()->getPurchaseOrderNumber())) {
            $body .= "\n" . $this->getProducer()->getPurchaseOrderNumber();
        }

        return $body;
    }
}
