<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class InvoiceItem extends BaseInvoiceItem
{
    protected array $roundable_fields = ['unit_cost'];

    /**
     * Return property -> value pair.
     */
    public function jsonSerialize(): array
    {
        $first_tax_rate = $this->getFirstTaxRate();
        $second_tax_rate = $this->getSecondTaxRate();

        $result = array_merge(
            parent::jsonSerialize(),
            [
                'discount_rate' => $this->getDiscountRate(),
                'description' => $this->getDescription(),
                'quantity' => $this->getQuantity(),
                'unit_cost' => $this->getUnitCost(),
                'first_tax_rate_id' => $this->getFirstTaxRateId(),
                'first_tax_value' => $this->getFirstTax(),
                'first_tax_name' => $first_tax_rate instanceof TaxRate ? $first_tax_rate->getName() : '',
                'first_tax_rate' => $first_tax_rate instanceof TaxRate ? $first_tax_rate->getPercentage() : 0,
                'second_tax_rate_id' => $this->getSecondTaxRateId(),
                'second_tax_value' => $this->getSecondTax(),
                'second_tax_name' => $second_tax_rate instanceof TaxRate ? $second_tax_rate->getName() : '',
                'second_tax_rate' => $second_tax_rate instanceof TaxRate ? $second_tax_rate->getPercentage() : 0,
                'second_tax_is_enabled' => $this->getSecondTaxIsEnabled(),
                'second_tax_is_compound' => $this->getSecondTaxIsCompound(),
                'subtotal' => $this->getSubtotal(),
                'subtotal_without_discount' => $this->getSubtotal() + $this->getDiscount(),
                'discount' => $this->getDiscount(),
                'total' => $this->getTotal(),
                'position' => $this->getPosition(),
                'project_id' => $this->getProjectId(),
            ]
        );

        if ($this->getParentType() == Invoice::class) {
            $result['time_record_ids'] = DB::executeFirstColumn('SELECT id FROM time_records WHERE invoice_item_id = ? AND invoice_type = ? AND is_trashed = ? ORDER BY id', $this->getId(), Invoice::INVOICE_TYPE, false);
            $result['expense_ids'] = DB::executeFirstColumn('SELECT id FROM expenses WHERE invoice_item_id = ? AND invoice_type = ? AND is_trashed = ? ORDER BY id', $this->getId(), Invoice::INVOICE_TYPE, false);

            if (empty($result['time_record_ids'])) {
                $result['time_record_ids'] = [];
            }

            if (empty($result['expense_ids'])) {
                $result['expense_ids'] = [];
            }
        }

        return $result;
    }

    /**
     * Return related first tax rate.
     *
     * @return TaxRate
     */
    public function &getFirstTaxRate()
    {
        return DataObjectPool::get(TaxRate::class, $this->getFirstTaxRateId());
    }

    /**
     * Get Second Tax Rate.
     *
     * @return TaxRate
     */
    public function &getSecondTaxRate()
    {
        return DataObjectPool::get(TaxRate::class, $this->getSecondTaxRateId());
    }

    public function getRoutingContext(): string
    {
        return 'invoice_item';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'invoice_id' => $this->getParentId(),
            'invoice_item_id' => $this->getId(),
        ];
    }

    /**
     * Return item currency.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->getParent() instanceof IInvoice ? $this->getParent()->getCurrency() : null;
    }

    // ---------------------------------------------------
    //  Utils
    // ---------------------------------------------------

    /**
     * Return first tax rate value string.
     *
     * @return string
     */
    public function getFirstTaxRatePercentageVerbose()
    {
        return $this->getFirstTaxRate() instanceof TaxRate ? $this->getFirstTaxRate()->getVerbosePercentage() : '-';
    }

    /**
     * Return second tax rate percentage verbose.
     *
     * @return string
     */
    public function getSecondTaxRatePercentageVerbose()
    {
        return $this->getSecondTaxRate() instanceof TaxRate ? $this->getSecondTaxRate()->getVerbosePercentage() : '-';
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        $this->validatePresenceOf('description') or $errors->addError('Item description is required', 'description');
        $this->validatePresenceOf('quantity') or $errors->addError('Quantity is required', 'quantity');

        parent::validate($errors);
    }

    /**
     * Save Invoice Object Item.
     *
     * @return bool
     */
    public function save()
    {
        if (!$this->getUnitCost()) {
            $this->setUnitCost(0);
        }

        $this->recalculate();

        return parent::save();
    }

    /**
     * Recalculate cached fields.
     */
    public function recalculate()
    {
        // round subtotal of item
        $subtotal = round($this->getUnitCost() * $this->getQuantity(), $this->getCurrency()->getDecimalSpaces());

        $unit_discount = 0;
        $discount = 0;
        if ($this->getDiscountRate()) {
            $unit_discount = $this->getUnitCost() * $this->getDiscountRate() / 100;
            $discount = $subtotal * ($this->getDiscountRate() / 100);
        }

        $this->setSubtotal($subtotal);
        $this->setDiscount($discount);

        $first_rate = $this->getFirstTaxRate() instanceof TaxRate ? $this->getFirstTaxRate()->getPercentage() : 0;

        $first_unit_tax_value = ($this->getUnitCost() - $unit_discount) * $first_rate / 100;
        $this->setFirstTax($this->getQuantity() * $first_unit_tax_value);

        if ($this->getSecondTaxIsEnabled()) {
            $second_rate = $this->getSecondTaxRate() instanceof TaxRate ? $this->getSecondTaxRate()->getPercentage() : 0;

            if ($this->getSecondTaxIsCompound()) {
                $second_unit_tax_value = ($this->getUnitCost() - $unit_discount + ($this->getQuantity() * $first_unit_tax_value)) * $second_rate / 100;
            } else {
                $second_unit_tax_value = ($this->getUnitCost() - $unit_discount) * $second_rate / 100;
            }
            $this->setSecondTax($this->getQuantity() * $second_unit_tax_value);
        }

        $this->setTotal($this->getSubtotal() + $this->getFirstTax() + $this->getSecondTax());
    }

    /**
     * Delete application object from database.
     *
     * @param bool $bulk
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Begin: drop invoice item @ ' . __CLASS__);

            if (empty($bulk)) {
                if ($time_record_ids = DB::executeFirstColumn('SELECT id FROM time_records WHERE invoice_item_id = ? AND invoice_type = ?', $this->getId(), Invoice::INVOICE_TYPE)) {
                    DB::execute('UPDATE time_records SET invoice_item_id = ?, invoice_type = ?, billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', 0, null, TimeRecord::BILLABLE, $time_record_ids);
                    TimeRecords::clearCacheFor($time_record_ids);
                }

                if ($expense_ids = DB::executeFirstColumn('SELECT id FROM expenses WHERE invoice_item_id = ? AND invoice_type = ?', $this->getId(), Invoice::INVOICE_TYPE)) {
                    DB::execute('UPDATE expenses SET invoice_item_id = ?, invoice_type = ?, billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', 0, null, Expense::BILLABLE, $expense_ids);
                    Expenses::clearCacheFor($expense_ids);
                }
            }

            parent::delete($bulk);

            DB::commit('Done: drop invoice item @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: drop invoice item @ ' . __CLASS__);
            throw $e;
        }
    }
}
