<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Interface that invoice objects need to implement for system to be able to export them.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage models
 */
interface IInvoiceExport
{
    /**
     * Return invoice number.
     *
     * @return int
     */
    public function getNumber();

    /**
     * Return date when invoice is due.
     *
     * @return DateValue
     */
    public function getDueOn();

    /**
     * Return date when invoice is issued.
     *
     * @return DateValue
     */
    public function getIssuedOn();

    /**
     * Get tax grouped by tax type.
     *
     * @return array
     */
    public function getTaxGroupedByType();

    // ---------------------------------------------------
    //  Status flags
    // ---------------------------------------------------

    /**
     * Is invoice issued.
     *
     * @return bool
     */
    public function isIssued();

    /**
     * Is invoice paid.
     *
     * @return bool
     */
    public function isPaid();
}
