<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class InvoiceNoteTemplates extends BaseInvoiceNoteTemplates
{
    /**
     * Get Default invoice note template.
     *
     * @return InvoiceNoteTemplate|DataObject
     */
    public static function getDefault()
    {
        return self::findOne(
            [
                'conditions' => [
                    'is_default = ?',
                    true,
                ],
            ]
        );
    }

    /**
     * Set default invoice note template.
     *
     * @return InvoiceNoteTemplate|DataObject|bool
     */
    public static function setDefault(InvoiceNoteTemplate $note_template = null)
    {
        if ($note_template && $note_template->getIsDefault()) {
            return $note_template;
        }

        DB::transact(function () use ($note_template) {
            DB::execute('UPDATE invoice_note_templates SET is_default = ?', false);

            if ($note_template) {
                DB::execute('UPDATE invoice_note_templates SET is_default = ? WHERE id = ?', true, $note_template->getId());
            }
        });

        self::clearCache();

        return $note_template
            ? DataObjectPool::reload(InvoiceNoteTemplate::class, $note_template->getId())
            : true;
    }

    public static function canAdd(User $user): bool
    {
        return $user->isOwner();
    }
}
