<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Invoice interface.
 *
 * @package ActiveCollab.modules.invoiving
 * @subpackage models
 */
interface IInvoiceBasedOn
{
    /**
     * Return preview array of items.
     *
     * @param  array $settings
     * @return array
     */
    public function previewInvoiceItems($settings = null, IUser $user = null);

    /**
     * Create new invoice instance based on parent object.
     *
     * @param  string      $number
     * @param  string      $client_address
     * @param  array|null  $additional
     * @return Invoice
     * @throws Angie\Error
     */
    public function createInvoice($number, Company $client = null, $client_address = null, $additional = null, IUser $user = null);

    /**
     * Return object ID.
     *
     * @return int
     */
    public function getId();
}
