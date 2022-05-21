<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Quickbooks invoice class.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage models
 */
class QuickbooksInvoice extends RemoteInvoice
{
    const EMAIL_STATUS_NOT_SET = 'NotSet';
    const EMAIL_STATUS_NEED_TO_SEND = 'NeedToSend';
    const EMAIL_STATUS_SENT = 'EmailSent';

    /**
     * Set email status.
     *
     * @param  string $email_status
     * @return string
     */
    public function setEmailStatus($email_status)
    {
        return $email_status != self::EMAIL_STATUS_NOT_SET ? $this->setAdditionalProperty('email_status', $email_status) : null;
    }

    /**
     * Set currency value.
     *
     * @param  string $value
     * @return string
     */
    public function setCurrency($value)
    {
        return $this->setAdditionalProperty('currency', (string) $value);
    }

    /**
     * Serialize data.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['status'] = $this->getStatus();
        $result['currency'] = $this->getCurrency();

        return $result;
    }

    /**
     * Return email status.
     *
     * @return string
     */
    public function getEmailStatus()
    {
        return $this->getAdditionalProperty('email_status', self::EMAIL_STATUS_NOT_SET);
    }

    /**
     * Return currency value.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->getAdditionalProperty('currency');
    }

    /**
     * Set items.
     *
     * @return array
     */
    public function setItems(array $items)
    {
        // remote invoices based on fixed budget types don't store items in additional data
        if ($this->getBasedOn() === 'fixed') {
            return [];
        }

        $attributes_to_keep_list = ['line_id', 'expense_ids', 'time_record_ids'];

        foreach ($items as $key => $attributes) {
            foreach (array_keys($attributes) as $sub_key) {
                if (!in_array($sub_key, $attributes_to_keep_list)) {
                    unset($items[$key][$sub_key]);
                }
            }
        }

        return parent::setItems($items);
    }

    /**
     * Return Inovoice status.
     *
     * @return string
     */
    public function getStatus()
    {
        if ($this->isCanceled()) {
            return RemoteInvoice::PAID_AND_CANCELED;
        } elseif ($this->isPaid()) {
            return RemoteInvoice::PAID;
        } elseif ($this->getBalance() < $this->getAmount()) {
            return RemoteInvoice::PARTIALLY_PAID;
        } elseif ($this->getEmailStatus() == self::EMAIL_STATUS_SENT) {
            return RemoteInvoice::SENT;
        }

        return RemoteInvoice::UNSENT;
    }
}
