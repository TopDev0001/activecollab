<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class BaseEstimate extends ApplicationObject implements IHistory, IAccessLog, IActivityLog, \Angie\Search\SearchItem\SearchItemInterface, ITrash, ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface, IInvoice, IInvoiceBasedOn, IProjectBasedOn, ICreatedOn, ICreatedBy, IUpdatedOn
{
    use IHistoryImplementation;
    use IAccessLogImplementation;
    use IActivityLogImplementation;
    use \Angie\Search\SearchItem\SearchItemImplementation;
    use ITrashImplementation;
    use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
    use IInvoiceImplementation;
    use IInvoiceBasedOnImplementation;
    use ICreatedOnImplementation;
    use ICreatedByImplementation;
    use IUpdatedOnImplementation {
        IInvoiceImplementation::canViewAccessLogs insteadof IAccessLogImplementation;
    }
    const MODEL_NAME = 'Estimate';
    const MANAGER_NAME = 'Estimates';

    protected string $table_name = 'estimates';
    protected array $fields = [
        'id',
        'name',
        'company_id',
        'company_name',
        'company_address',
        'currency_id',
        'language_id',
        'discount_rate',
        'subtotal',
        'discount',
        'tax',
        'total',
        'balance_due',
        'paid_amount',
        'note',
        'private_note',
        'status',
        'second_tax_is_enabled',
        'second_tax_is_compound',
        'recipients',
        'email_from_id',
        'email_from_name',
        'email_from_email',
        'email_subject',
        'email_body',
        'created_on',
        'created_by_id',
        'created_by_name',
        'created_by_email',
        'updated_on',
        'sent_on',
        'sent_by_id',
        'sent_by_name',
        'sent_by_email',
        'hash',
        'is_trashed',
        'trashed_on',
        'trashed_by_id',
    ];

    protected array $default_field_values = [
        'name' => '',
        'company_id' => 0,
        'currency_id' => 0,
        'language_id' => 0,
        'discount_rate' => 0,
        'subtotal' => 0.0,
        'discount' => 0.0,
        'tax' => 0.0,
        'total' => 0.0,
        'balance_due' => 0.0,
        'paid_amount' => 0.0,
        'status' => 'draft',
        'second_tax_is_enabled' => false,
        'second_tax_is_compound' => false,
        'is_trashed' => false,
        'trashed_by_id' => 0,
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
            return $underscore ? 'estimate' : 'Estimate';
        } else {
            return $underscore ? 'estimates' : 'Estimates';
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
     * Return value of name field.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getFieldValue('name');
    }

    /**
     * Set value of name field.
     *
     * @param  string $value
     * @return string
     */
    public function setName($value)
    {
        return $this->setFieldValue('name', $value);
    }

    /**
     * Return value of company_id field.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->getFieldValue('company_id');
    }

    /**
     * Set value of company_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCompanyId($value)
    {
        return $this->setFieldValue('company_id', $value);
    }

    /**
     * Return value of company_name field.
     *
     * @return string
     */
    public function getCompanyName()
    {
        return $this->getFieldValue('company_name');
    }

    /**
     * Set value of company_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setCompanyName($value)
    {
        return $this->setFieldValue('company_name', $value);
    }

    /**
     * Return value of company_address field.
     *
     * @return string
     */
    public function getCompanyAddress()
    {
        return $this->getFieldValue('company_address');
    }

    /**
     * Set value of company_address field.
     *
     * @param  string $value
     * @return string
     */
    public function setCompanyAddress($value)
    {
        return $this->setFieldValue('company_address', $value);
    }

    /**
     * Return value of currency_id field.
     *
     * @return int
     */
    public function getCurrencyId()
    {
        return $this->getFieldValue('currency_id');
    }

    /**
     * Set value of currency_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCurrencyId($value)
    {
        return $this->setFieldValue('currency_id', $value);
    }

    /**
     * Return value of language_id field.
     *
     * @return int
     */
    public function getLanguageId()
    {
        return $this->getFieldValue('language_id');
    }

    /**
     * Set value of language_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setLanguageId($value)
    {
        return $this->setFieldValue('language_id', $value);
    }

    /**
     * Return value of discount_rate field.
     *
     * @return int
     */
    public function getDiscountRate()
    {
        return $this->getFieldValue('discount_rate');
    }

    /**
     * Set value of discount_rate field.
     *
     * @param  int $value
     * @return int
     */
    public function setDiscountRate($value)
    {
        return $this->setFieldValue('discount_rate', $value);
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
     * Return value of tax field.
     *
     * @return float
     */
    public function getTax()
    {
        return $this->getFieldValue('tax');
    }

    /**
     * Set value of tax field.
     *
     * @param  float $value
     * @return float
     */
    public function setTax($value)
    {
        return $this->setFieldValue('tax', $value);
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
     * Return value of balance_due field.
     *
     * @return float
     */
    public function getBalanceDue()
    {
        return $this->getFieldValue('balance_due');
    }

    /**
     * Set value of balance_due field.
     *
     * @param  float $value
     * @return float
     */
    public function setBalanceDue($value)
    {
        return $this->setFieldValue('balance_due', $value);
    }

    /**
     * Return value of paid_amount field.
     *
     * @return float
     */
    public function getPaidAmount()
    {
        return $this->getFieldValue('paid_amount');
    }

    /**
     * Set value of paid_amount field.
     *
     * @param  float $value
     * @return float
     */
    public function setPaidAmount($value)
    {
        return $this->setFieldValue('paid_amount', $value);
    }

    /**
     * Return value of note field.
     *
     * @return string
     */
    public function getNote()
    {
        return $this->getFieldValue('note');
    }

    /**
     * Set value of note field.
     *
     * @param  string $value
     * @return string
     */
    public function setNote($value)
    {
        return $this->setFieldValue('note', $value);
    }

    /**
     * Return value of private_note field.
     *
     * @return string
     */
    public function getPrivateNote()
    {
        return $this->getFieldValue('private_note');
    }

    /**
     * Set value of private_note field.
     *
     * @param  string $value
     * @return string
     */
    public function setPrivateNote($value)
    {
        return $this->setFieldValue('private_note', $value);
    }

    /**
     * Return value of status field.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getFieldValue('status');
    }

    /**
     * Set value of status field.
     *
     * @param  string $value
     * @return string
     */
    public function setStatus($value)
    {
        return $this->setFieldValue('status', $value);
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
     * Return value of recipients field.
     *
     * @return string
     */
    public function getRecipients()
    {
        return $this->getFieldValue('recipients');
    }

    /**
     * Set value of recipients field.
     *
     * @param  string $value
     * @return string
     */
    public function setRecipients($value)
    {
        return $this->setFieldValue('recipients', $value);
    }

    /**
     * Return value of email_from_id field.
     *
     * @return int
     */
    public function getEmailFromId()
    {
        return $this->getFieldValue('email_from_id');
    }

    /**
     * Set value of email_from_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setEmailFromId($value)
    {
        return $this->setFieldValue('email_from_id', $value);
    }

    /**
     * Return value of email_from_name field.
     *
     * @return string
     */
    public function getEmailFromName()
    {
        return $this->getFieldValue('email_from_name');
    }

    /**
     * Set value of email_from_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setEmailFromName($value)
    {
        return $this->setFieldValue('email_from_name', $value);
    }

    /**
     * Return value of email_from_email field.
     *
     * @return string
     */
    public function getEmailFromEmail()
    {
        return $this->getFieldValue('email_from_email');
    }

    /**
     * Set value of email_from_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setEmailFromEmail($value)
    {
        return $this->setFieldValue('email_from_email', $value);
    }

    /**
     * Return value of email_subject field.
     *
     * @return string
     */
    public function getEmailSubject()
    {
        return $this->getFieldValue('email_subject');
    }

    /**
     * Set value of email_subject field.
     *
     * @param  string $value
     * @return string
     */
    public function setEmailSubject($value)
    {
        return $this->setFieldValue('email_subject', $value);
    }

    /**
     * Return value of email_body field.
     *
     * @return string
     */
    public function getEmailBody()
    {
        return $this->getFieldValue('email_body');
    }

    /**
     * Set value of email_body field.
     *
     * @param  string $value
     * @return string
     */
    public function setEmailBody($value)
    {
        return $this->setFieldValue('email_body', $value);
    }

    /**
     * Return value of created_on field.
     *
     * @return DateTimeValue
     */
    public function getCreatedOn()
    {
        return $this->getFieldValue('created_on');
    }

    /**
     * Set value of created_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setCreatedOn($value)
    {
        return $this->setFieldValue('created_on', $value);
    }

    /**
     * Return value of created_by_id field.
     *
     * @return int
     */
    public function getCreatedById()
    {
        return $this->getFieldValue('created_by_id');
    }

    /**
     * Set value of created_by_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setCreatedById($value)
    {
        return $this->setFieldValue('created_by_id', $value);
    }

    /**
     * Return value of created_by_name field.
     *
     * @return string
     */
    public function getCreatedByName()
    {
        return $this->getFieldValue('created_by_name');
    }

    /**
     * Set value of created_by_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setCreatedByName($value)
    {
        return $this->setFieldValue('created_by_name', $value);
    }

    /**
     * Return value of created_by_email field.
     *
     * @return string
     */
    public function getCreatedByEmail()
    {
        return $this->getFieldValue('created_by_email');
    }

    /**
     * Set value of created_by_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setCreatedByEmail($value)
    {
        return $this->setFieldValue('created_by_email', $value);
    }

    /**
     * Return value of updated_on field.
     *
     * @return DateTimeValue
     */
    public function getUpdatedOn()
    {
        return $this->getFieldValue('updated_on');
    }

    /**
     * Set value of updated_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setUpdatedOn($value)
    {
        return $this->setFieldValue('updated_on', $value);
    }

    /**
     * Return value of sent_on field.
     *
     * @return DateTimeValue
     */
    public function getSentOn()
    {
        return $this->getFieldValue('sent_on');
    }

    /**
     * Set value of sent_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setSentOn($value)
    {
        return $this->setFieldValue('sent_on', $value);
    }

    /**
     * Return value of sent_by_id field.
     *
     * @return int
     */
    public function getSentById()
    {
        return $this->getFieldValue('sent_by_id');
    }

    /**
     * Set value of sent_by_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setSentById($value)
    {
        return $this->setFieldValue('sent_by_id', $value);
    }

    /**
     * Return value of sent_by_name field.
     *
     * @return string
     */
    public function getSentByName()
    {
        return $this->getFieldValue('sent_by_name');
    }

    /**
     * Set value of sent_by_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setSentByName($value)
    {
        return $this->setFieldValue('sent_by_name', $value);
    }

    /**
     * Return value of sent_by_email field.
     *
     * @return string
     */
    public function getSentByEmail()
    {
        return $this->getFieldValue('sent_by_email');
    }

    /**
     * Set value of sent_by_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setSentByEmail($value)
    {
        return $this->setFieldValue('sent_by_email', $value);
    }

    /**
     * Return value of hash field.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->getFieldValue('hash');
    }

    /**
     * Set value of hash field.
     *
     * @param  string $value
     * @return string
     */
    public function setHash($value)
    {
        return $this->setFieldValue('hash', $value);
    }

    /**
     * Return value of is_trashed field.
     *
     * @return bool
     */
    public function getIsTrashed()
    {
        return $this->getFieldValue('is_trashed');
    }

    /**
     * Set value of is_trashed field.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIsTrashed($value)
    {
        return $this->setFieldValue('is_trashed', $value);
    }

    /**
     * Return value of trashed_on field.
     *
     * @return DateTimeValue
     */
    public function getTrashedOn()
    {
        return $this->getFieldValue('trashed_on');
    }

    /**
     * Set value of trashed_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setTrashedOn($value)
    {
        return $this->setFieldValue('trashed_on', $value);
    }

    /**
     * Return value of trashed_by_id field.
     *
     * @return int
     */
    public function getTrashedById()
    {
        return $this->getFieldValue('trashed_by_id');
    }

    /**
     * Set value of trashed_by_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setTrashedById($value)
    {
        return $this->setFieldValue('trashed_by_id', $value);
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
                case 'name':
                    return parent::setFieldValue($name, (string) $value);
                case 'company_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'company_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'company_address':
                    return parent::setFieldValue($name, (string) $value);
                case 'currency_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'language_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'discount_rate':
                    return parent::setFieldValue($name, (int) $value);
                case 'subtotal':
                    return parent::setFieldValue($name, (float) $value);
                case 'discount':
                    return parent::setFieldValue($name, (float) $value);
                case 'tax':
                    return parent::setFieldValue($name, (float) $value);
                case 'total':
                    return parent::setFieldValue($name, (float) $value);
                case 'balance_due':
                    return parent::setFieldValue($name, (float) $value);
                case 'paid_amount':
                    return parent::setFieldValue($name, (float) $value);
                case 'note':
                    return parent::setFieldValue($name, (string) $value);
                case 'private_note':
                    return parent::setFieldValue($name, (string) $value);
                case 'status':
                    return parent::setFieldValue($name, (empty($value) ? null : (string) $value));
                case 'second_tax_is_enabled':
                    return parent::setFieldValue($name, (bool) $value);
                case 'second_tax_is_compound':
                    return parent::setFieldValue($name, (bool) $value);
                case 'recipients':
                    return parent::setFieldValue($name, (string) $value);
                case 'email_from_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'email_from_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'email_from_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'email_subject':
                    return parent::setFieldValue($name, (string) $value);
                case 'email_body':
                    return parent::setFieldValue($name, (string) $value);
                case 'created_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'created_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'created_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'created_by_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'updated_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'sent_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'sent_by_id':
                    return parent::setFieldValue($name, (int) $value);
                case 'sent_by_name':
                    return parent::setFieldValue($name, (string) $value);
                case 'sent_by_email':
                    return parent::setFieldValue($name, (string) $value);
                case 'hash':
                    return parent::setFieldValue($name, (string) $value);
                case 'is_trashed':
                    return parent::setFieldValue($name, (bool) $value);
                case 'trashed_on':
                    return parent::setFieldValue($name, datetimeval($value));
                case 'trashed_by_id':
                    return parent::setFieldValue($name, (int) $value);
            }

            throw new InvalidParamError('name', $name, "Field $name does not exist in this table");
        }
    }
}
