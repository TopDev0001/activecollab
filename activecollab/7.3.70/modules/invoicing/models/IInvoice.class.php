<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Text\VariableProcessor\VariableProcessorInterface;

/**
 * Interface that all invoice instances need to implement.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage models
 */
interface IInvoice
{
    /**
     * @return string
     */
    public function getName();

    /**
     * Return document currency.
     *
     * @return Currency
     */
    public function getCurrency();

    /**
     * Return document language.
     *
     * @return Language
     */
    public function getLanguage();

    public function hasCJKCharacters(): bool;

    /**
     * Return company.
     *
     * @return Company
     */
    public function &getCompany();

    /**
     * Get company name.
     *
     * @return string
     */
    public function getCompanyName();

    /**
     * Return company address.
     *
     * @return string
     */
    public function getCompanyAddress();

    // ---------------------------------------------------
    //  Items
    // ---------------------------------------------------

    /**
     * Get invoice items.
     *
     * @return array
     */
    public function getItems();

    public function countItems(): int;

    public function addItem(
        array $attributes,
        int $position,
        bool $bulk = false,
        VariableProcessorInterface $variable_processor = null
    );

    /**
     * Add items from attributes.
     */
    public function addItemsFromAttributes(array $attributes);

    /**
     * Update items from attributes.
     */
    public function updateItemsFromAttributes(array $attributes);

    // ---------------------------------------------------
    //  Calculation
    // ---------------------------------------------------

    /**
     * Return invoice total.
     *
     * @return float
     */
    public function getSubTotal();

    /**
     * Return calculated tax.
     *
     * @return float
     */
    public function getTax();

    /**
     * Return true if secon tax is enabled.
     *
     * @return bool
     */
    public function getSecondTaxIsEnabled();

    /**
     * Get invoice total.
     *
     * @return float
     */
    public function getTotal();

    /**
     * Check if invoice total require rounding.
     *
     * @return bool
     */
    public function requireRounding();

    /**
     * Return total rounded to a precision defined by the invoice currency.
     *
     * @return float
     */
    public function getRoundedTotal();

    /**
     * Get rounding difference.
     *
     * @return float
     */
    public function getRoundingDifference();

    /**
     * Calculate total by walking through list of items.
     */
    public function recalculate();
}
