<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseInvoiceItemTemplate extends ApplicationObject implements ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface
{
    use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
    const MODEL_NAME = 'InvoiceItemTemplate';
    const MANAGER_NAME = 'InvoiceItemTemplates';

    protected string $table_name = 'invoice_item_templates';
    protected array $fields = [
        'id',
        'first_tax_rate_id',
        'second_tax_rate_id',
        'description',
        'quantity',
        'unit_cost',
        'position',
    ];

    protected array $default_field_values = [
        'first_tax_rate_id' => 0,
        'second_tax_rate_id' => 0,
        'quantity' => 1.0,
        'unit_cost' => 0.0,
        'position' => 0,
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
            return $underscore ? 'invoice_item_template' : 'InvoiceItemTemplate';
        } else {
            return $underscore ? 'invoice_item_templates' : 'InvoiceItemTemplates';
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
                case 'first_tax_rate_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'second_tax_rate_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'description':
                    return parent::setFieldValue($name, (string) $value);
                case 'quantity':
                    return parent::setFieldValue($name, (float) $value);
                case 'unit_cost':
                    return parent::setFieldValue($name, (float) $value);
                case 'position':
                    return parent::setFieldValue($name, (int) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
