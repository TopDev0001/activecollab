<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseInvoiceItem extends ApplicationObject implements ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, IChild
{
    use IRoundFieldValueToDecimalPrecisionImplementation;
    use IChildImplementation;
    const MODEL_NAME = 'InvoiceItem';
    const MANAGER_NAME = 'InvoiceItems';

    protected string $table_name = 'invoice_items';
    protected array $fields = [
        'id',
        'parent_type',
        'parent_id',
        'first_tax_rate_id',
        'second_tax_rate_id',
        'discount_rate',
        'description',
        'quantity',
        'unit_cost',
        'subtotal',
        'discount',
        'first_tax',
        'second_tax',
        'total',
        'second_tax_is_enabled',
        'second_tax_is_compound',
        'position',
        'project_id',
    ];

    protected array $default_field_values = [
        'first_tax_rate_id' => 0,
        'second_tax_rate_id' => 0,
        'discount_rate' => 0.0,
        'quantity' => 1.0,
        'unit_cost' => 0.0,
        'subtotal' => 0.0,
        'discount' => 0.0,
        'first_tax' => 0.0,
        'second_tax' => 0.0,
        'total' => 0.0,
        'second_tax_is_enabled' => false,
        'second_tax_is_compound' => false,
        'project_id' => 0,
    ];

    protected array $primary_key = [
        'id',
    ];

    public function getModelName(
        bool $underscore = false,
        bool $singular = false
    ): string
    {
        if ($singular) {
            return $underscore ? 'invoice_item' : 'InvoiceItem';
        } else {
            return $underscore ? 'invoice_items' : 'InvoiceItems';
        }
    }

    protected ?string $auto_increment = 'id';
    // ---------------------------------------------------
    //  Fields
    // ---------------------------------------------------

    /**
     * Return value of id field.
     *
     * @return int
     */
    public function getId()
    {
        return $this->getFieldValue('id');
    }

    /**
     * Set value of id field.
     *
     * @param  int $value
     * @return int
     */
    public function setId($value)
    {
        return $this->setFieldValue('id', $value);
    }

    /**
     * Return value of parent_type field.
     *
     * @return string
     */
    public function getParentType()
    {
        return $this->getFieldValue('parent_type');
    }

    /**
     * Set value of parent_type field.
     *
     * @param  string $value
     * @return string
     */
    public function setParentType($value)
    {
        return $this->setFieldValue('parent_type', $value);
    }

    /**
     * Return value of parent_id field.
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->getFieldValue('parent_id');
    }

    /**
     * Set value of parent_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setParentId($value)
    {
        return $this->setFieldValue('parent_id', $value);
    }

    /**
     * Return value of first_tax_rate_id field.
     *
     * @return int
     */
    public function getFirstTaxRateId()
    {
        return $this->getFieldValue('first_tax_rate_id');
    }

    /**
     * Set value of first_tax_rate_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setFirstTaxRateId($value)
    {
        return $this->setFieldValue('first_tax_rate_id', $value);
    }

    /**
     * Return value of second_tax_rate_id field.
     *
     * @return int
     */
    public function getSecondTaxRateId()
    {
        return $this->getFieldValue('second_tax_rate_id');
    }

    /**
     * Set value of second_tax_rate_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setSecondTaxRateId($value)
    {
        return $this->setFieldValue('second_tax_rate_id', $value);
    }

    /**
     * Return value of discount_rate field.
     *
     * @return float
     */
    public function getDiscountRate()
    {
        return $this->getFieldValue('discount_rate');
    }

    /**
     * Set value of discount_rate field.
     *
     * @param  float $value
     * @return float
     */
    public function setDiscountRate($value)
    {
        return $this->setFieldValue('discount_rate', $value);
    }

    /**
     * Return value of description field.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getFieldValue('description');
    }

    /**
     * Set value of description field.
     *
     * @param  string $value
     * @return string
     */
    public function setDescription($value)
    {
        return $this->setFieldValue('description', $value);
    }

    /**
     * Return value of quantity field.
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->getFieldValue('quantity');
    }

    /**
     * Set value of quantity field.
     *
     * @param  float $value
     * @return float
     */
    public function setQuantity($value)
    {
        return $this->setFieldValue('quantity', $value);
    }

    /**
     * Return value of unit_cost field.
     *
     * @return float
     */
    public function getUnitCost()
    {
        return $this->getFieldValue('unit_cost');
    }

    /**
     * Set value of unit_cost field.
     *
     * @param  float $value
     * @return float
     */
    public function setUnitCost($value)
    {
        return $this->setFieldValue('unit_cost', $value);
    }

    /**
     * Return value of subtotal field.
     *
     * @return float
     */
    public function getSubtotal()
    {
        return $this->getFieldValue('subtotal');
    }

    /**
     * Set value of subtotal field.
     *
     * @param  float $value
     * @return float
     */
    public function setSubtotal($value)
    {
        return $this->setFieldValue('subtotal', $value);
    }

    /**
     * Return value of discount field.
     *
     * @return float
     */
    public function getDiscount()
    {
        return $this->getFieldValue('discount');
    }

    /**
     * Set value of discount field.
     *
     * @param  float $value
     * @return float
     */
    public function setDiscount($value)
    {
        return $this->setFieldValue('discount', $value);
    }

    /**
     * Return value of first_tax field.
     *
     * @return float
     */
    public function getFirstTax()
    {
        return $this->getFieldValue('first_tax');
    }

    /**
     * Set value of first_tax field.
     *
     * @param  float $value
     * @return float
     */
    public function setFirstTax($value)
    {
        return $this->setFieldValue('first_tax', $value);
    }

    /**
     * Return value of second_tax field.
     *
     * @return float
     */
    public function getSecondTax()
    {
        return $this->getFieldValue('second_tax');
    }

    /**
     * Set value of second_tax field.
     *
     * @param  float $value
     * @return float
     */
    public function setSecondTax($value)
    {
        return $this->setFieldValue('second_tax', $value);
    }

    /**
     * Return value of total field.
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->getFieldValue('total');
    }

    /**
     * Set value of total field.
     *
     * @param  float $value
     * @return float
     */
    public function setTotal($value)
    {
        return $this->setFieldValue('total', $value);
    }

    /**
     * Return value of second_tax_is_enabled field.
     *
     * @return bool
     */
    public function getSecondTaxIsEnabled()
    {
        return $this->getFieldValue('second_tax_is_enabled');
    }

    /**
     * Set value of second_tax_is_enabled field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setSecondTaxIsEnabled($value)
    {
        return $this->setFieldValue('second_tax_is_enabled', $value);
    }

    /**
     * Return value of second_tax_is_compound field.
     *
     * @return bool
     */
    public function getSecondTaxIsCompound()
    {
        return $this->getFieldValue('second_tax_is_compound');
    }

    /**
     * Set value of second_tax_is_compound field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setSecondTaxIsCompound($value)
    {
        return $this->setFieldValue('second_tax_is_compound', $value);
    }

    /**
     * Return value of position field.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->getFieldValue('position');
    }

    /**
     * Set value of position field.
     *
     * @param  int $value
     * @return int
     */
    public function setPosition($value)
    {
        return $this->setFieldValue('position', $value);
    }

    /**
     * Return value of project_id field.
     *
     * @return int
     */
    public function getProjectId()
    {
        return $this->getFieldValue('project_id');
    }

    /**
     * Set value of project_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setProjectId($value)
    {
        return $this->setFieldValue('project_id', $value);
    }

    /**
     * Set value of specific field.
     *
     * @param  mixed $value
     * @return mixed
     */
    public function setFieldValue(string $name, $value)
    {
        if ($value === null) {
            return parent::setFieldValue($name, null);
        } else {
            switch ($name) {
                case 'id':
                    return parent::setFieldValue($name, (int) $value);
                case 'parent_type':
                    return parent::setFieldValue($name, (string) $value);
                case 'parent_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'first_tax_rate_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'second_tax_rate_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'discount_rate':
                    return parent::setFieldValue($name, (float) $value);
                case 'description':
                    return parent::setFieldValue($name, (string) $value);
                case 'quantity':
                    return parent::setFieldValue($name, (float) $value);
                case 'unit_cost':
                    return parent::setFieldValue($name, (float) $value);
                case 'subtotal':
                    return parent::setFieldValue($name, (float) $value);
                case 'discount':
                    return parent::setFieldValue($name, (float) $value);
                case 'first_tax':
                    return parent::setFieldValue($name, (float) $value);
                case 'second_tax':
                    return parent::setFieldValue($name, (float) $value);
                case 'total':
                    return parent::setFieldValue($name, (float) $value);
                case 'second_tax_is_enabled':
                    return parent::setFieldValue($name, (bool) $value);
                case 'second_tax_is_compound':
                    return parent::setFieldValue($name, (bool) $value);
                case 'position':
                    return parent::setFieldValue($name, (int) $value);
                case 'project_id':
                    return parent::setFieldValue($name, (int) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
