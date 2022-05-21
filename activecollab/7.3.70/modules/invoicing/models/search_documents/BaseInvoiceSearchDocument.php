<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchDocument\SearchDocument;

/**
 * @method IInvoice|Invoice|Estimate getProducer()
 */
abstract class BaseInvoiceSearchDocument extends SearchDocument
{
    protected function getBody()
    {
        $bits = [
            parent::getBody(),
        ];

        if (!empty($this->getProducer()->getCompanyName())) {
            $bits[] = $this->getProducer()->getCompanyName();
        }

        if (!empty($this->getProducer()->getCompanyAddress())) {
            $bits[] = $this->getProducer()->getCompanyAddress();
        }

        if (!empty($this->getProducer()->getRecipients())) {
            $bits[] = $this->getProducer()->getRecipients();
        }

        if (!empty($this->getProducer()->getNote())) {
            $bits[] = $this->getProducer()->getNote();
        }

        if (!empty($this->getProducer()->getPrivateNote())) {
            $bits[] = $this->getProducer()->getPrivateNote();
        }

        $item_descriptions = DB::executeFirstColumn(
            'SELECT `description` FROM `invoice_items` WHERE `parent_type` = ? AND `parent_id` = ? ORDER BY `position`',
            get_class($this->getProducer()),
            $this->getProducer()->getId()
        );

        if (!empty($item_descriptions)) {
            array_push($bits, ...$item_descriptions);
        }

        return implode("\n", $bits);
    }
}
