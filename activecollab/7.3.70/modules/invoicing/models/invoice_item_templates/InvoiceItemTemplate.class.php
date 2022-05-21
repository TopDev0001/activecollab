<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class InvoiceItemTemplate extends BaseInvoiceItemTemplate
{
    /**
     * Second tax rate cache.
     *
     * @var bool
     */
    public $second_tax_rate = false;

    /**
     * cached value of tax.
     *
     * @var TaxRate
     */
    private $first_tax_rate = false;

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['description'] = $this->getDescription();
        if ($first_tax_rate = $this->getFirstTaxRate()) {
            $result['first_tax_rate_id'] = $first_tax_rate->getId();
            $result['first_tax_rate_value'] = $first_tax_rate->getPercentage();
        }
        if ($second_tax_rate = $this->getSecondTaxRate()) {
            $result['second_tax_rate_id'] = $second_tax_rate->getId();
            $result['second_tax_rate_value'] = $second_tax_rate->getPercentage();
        }
        $result['quantity'] = $this->getQuantity();
        $result['unit_cost'] = $this->getUnitCost();

        return $result;
    }

    /**
     * Return tax rate.
     *
     * @return TaxRate
     */
    public function getFirstTaxRate()
    {
        if ($this->first_tax_rate === false) {
            $this->first_tax_rate = DataObjectPool::get(TaxRate::class, $this->getFirstTaxRateId());
        }

        return $this->first_tax_rate;
    }

    /**
     * Get Second Tax Rate.
     *
     * @return TaxRate
     */
    public function getSecondTaxRate()
    {
        if ($this->second_tax_rate === false) {
            $this->second_tax_rate = DataObjectPool::get(TaxRate::class, $this->getSecondTaxRateId());
        }

        return $this->second_tax_rate;
    }

    public function getRoutingContext(): string
    {
        return 'invoice_item_template';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'invoice_item_template_id' => $this->getId(),
        ];
    }

    public function canView(User $user): bool
    {
        return $user->isOwner();
    }

    public function canEdit(User $user): bool
    {
        return $user->isOwner();
    }

    public function canDelete(User $user): bool
    {
        return $user->isOwner();
    }

    public function validate(ValidationErrors & $errors)
    {
        if (!$this->validatePresenceOf('description')) {
            $errors->fieldValueIsRequired('description');
        }

        if (!$this->validatePresenceOf('quantity')) {
            $errors->fieldValueIsRequired('quantity');
        }

        parent::validate($errors);
    }

    public function save()
    {
        if (!$this->getUnitCost()) {
            $this->setUnitCost(0);
        }

        parent::save();
    }
}
