<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class InvoiceItemTemplates extends BaseInvoiceItemTemplates
{
    /**
     * Find by tax mode.
     *
     * @param  bool                  $two_taxes
     * @return InvoiceItemTemplate[]
     */
    public static function findByTaxMode($two_taxes = true)
    {
        if ($two_taxes) {
            return self::find([
                'order' => 'description ASC',
            ]);
        } else {
            return self::find([
                'conditions' => ['second_tax_rate_id < ?', 1],
                'order' => 'description ASC',
            ]);
        }
    }

    public static function canAdd(User $user): bool
    {
        return $user->isOwner();
    }
}
