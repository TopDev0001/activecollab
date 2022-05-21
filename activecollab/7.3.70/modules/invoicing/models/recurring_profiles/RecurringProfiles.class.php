<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Invoicing\Utils\InvoiceAttributesProcessor\InvoiceAttributesProcessorInterface;
use ActiveCollab\Module\Invoicing\Utils\InvoiceNumberSuggester\InvoiceNumberSuggesterInterface;

class RecurringProfiles extends BaseRecurringProfiles
{
    /**
     * Return new collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if ($collection_name === 'active_profiles') {
            // do nothing
        } elseif (str_starts_with($collection_name, 'expired_profiles')) {
            $collection->setOrderBy('start_on DESC, id DESC');

            $bits = explode('_', $collection_name);
            $collection->setPagination(array_pop($bits), 30);
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): RecurringProfile
    {
        $attributes = AngieApplication::getContainer()
            ->get(InvoiceAttributesProcessorInterface::class)
                ->prepareAttributesForNewRecurringProfile($attributes);

        try {
            DB::beginWork('Begin: create new recurring profile @ ' . __CLASS__);

            $recurring_profile = parent::create($attributes, $save, $announce); // @TODO Announcement should send after items are added to the invoice

            if ($recurring_profile instanceof RecurringProfile) {
                $recurring_profile->addItemsFromAttributes($attributes);
            }

            DB::commit('Done: create new recurring profile @ ' . __CLASS__);

            if ($recurring_profile instanceof RecurringProfile) {
                self::processProfile($recurring_profile, DateTimeValue::now()->getSystemDate());
            }

            return $recurring_profile;
        } catch (Exception $e) {
            DB::rollback('Rollback: create new recurring profile @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * @param DataObject|RecurringProfile $instance
     */
    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): RecurringProfile
    {
        try {
            DB::beginWork('Begin: update the recurring profile @ ' . __CLASS__);

            if (isset($attributes['start_on'])) {
                $instance->validateStartOn(DateValue::makeFromString($attributes['start_on']));
            }

            parent::update($instance, $attributes, $save);
            $instance->updateItemsFromAttributes($attributes);

            DB::commit('Done: update the recurring profile @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: update the recurring profile @ ' . __CLASS__);
            throw $e;
        }

        self::processProfile($instance, DateTimeValue::now()->getSystemDate());

        return $instance;
    }

    /**
     * Process profile.
     *
     * @return Invoice|null
     */
    private static function processProfile(RecurringProfile $recurring_profile, DateValue $date)
    {
        if ($recurring_profile->shouldSendOn($date)) {
            $invoice = $recurring_profile->createInvoice(
                AngieApplication::getContainer()
                    ->get(InvoiceNumberSuggesterInterface::class)
                        ->suggest(),
                null,
                null,
                [
                    'trigger_date' => $date,
                ]
            );

            $safe_to_send = true;

            if (AngieApplication::isOnDemand()) {
                $safe_to_send = OnDemand::isItSafeToSendInvoice(
                    $recurring_profile,
                    $recurring_profile->getRecipientInstances()
                );
            }

            if ($recurring_profile->getAutoIssue() && $safe_to_send) {
                $invoice->send(
                    $recurring_profile->getCreatedBy(),
                    Users::findByAddressList($recurring_profile->getRecipients()),
                    $recurring_profile->getEmailSubject(),
                    $recurring_profile->getEmailBody()
                );

                AngieApplication::notifications()
                    ->notifyAbout('invoicing/invoice_generated_via_recurring_profile', $invoice)
                    ->setProfile($recurring_profile)
                    ->setInvoice($invoice)
                    ->sendToFinancialManagers(true);
            } else {
                AngieApplication::notifications()
                    ->notifyAbout('invoicing/draft_invoice_created_via_recurring_profile', $invoice)
                    ->setProfile($recurring_profile)
                    ->sendToFinancialManagers(true);
            }

            return $invoice;
        }

        return null;
    }

    public static function canAdd(User $user): bool
    {
        return $user->isFinancialManager();
    }
}
