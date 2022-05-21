<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class InvoiceGeneratedViaRecurringProfileNotification extends RecurringProfileNotification
{
    /**
     * Set invoice.
     *
     * @return RecurringProfileNotification
     */
    public function &setInvoice(Invoice $invoice)
    {
        $this->setAdditionalProperty('invoice_id', $invoice->getId());

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'profile' => $this->getProfile(),
            'invoice_recipients' => $this->getInvoice()->getRecipientInstances(),
        ];
    }

    /**
     * @return Invoice|DataObject
     */
    public function getInvoice()
    {
        return DataObjectPool::get(
            Invoice::class,
            $this->getAdditionalProperty('invoice_id')
        );
    }
}
