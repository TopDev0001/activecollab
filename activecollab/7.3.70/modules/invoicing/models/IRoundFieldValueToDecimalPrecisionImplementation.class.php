<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait IRoundFieldValueToDecimalPrecisionImplementation
{
    /**
     * Decimal spaces in this object.
     *
     * @var int
     */
    protected $decimal_precision = false;

    /**
     * Get Field Value.
     *
     * @param  string      $field
     * @param  null        $default
     * @return float|mixed
     */
    public function getFieldValue($field, $default = null)
    {
        if (isset($this->roundable_fields) && in_array($field, $this->roundable_fields)) {
            return round(parent::getFieldValue($field, $default), $this->getDecimalPrecision());
        }

        return parent::getFieldValue($field, $default);
    }

    /**
     * Get decimal scale.
     *
     * @return int
     */
    public function getDecimalPrecision()
    {
        if ($this->decimal_precision === false) {
            $currency = $this->getCurrency();

            $this->decimal_precision = $currency instanceof Currency ? $currency->getDecimalSpaces() : 2;
        }

        return $this->decimal_precision;
    }

    /**
     * @return Currency
     */
    abstract public function getCurrency();

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Set field value.
     *
     * @param  string      $name
     * @param  mixed       $value
     * @return float|mixed
     */
    public function setFieldValue($name, $value)
    {
        if (isset($this->roundable_fields) && in_array($name, $this->roundable_fields)) {
            return parent::setFieldValue($name, round($value, $this->getDecimalPrecision()));
        } else {
            return parent::setFieldValue($name, $value);
        }
    }
}
