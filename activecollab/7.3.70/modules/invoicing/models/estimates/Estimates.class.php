<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Text\VariableProcessor\VariableProcessorInterface;
use ActiveCollab\Module\Invoicing\Utils\InvoiceAttributesProcessor\InvoiceAttributesProcessorInterface;
use ActiveCollab\Module\Invoicing\Utils\VariableProcessor\Factory\VariableProcessorFactoryInterface;

class Estimates extends BaseEstimates
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

        if ($collection_name == 'active_estimates') {
            $collection->setConditions('status NOT IN (?) AND is_trashed = ?', [Estimate::WON, Estimate::LOST], false);
        } else {
            if ($collection_name && str_starts_with($collection_name, 'archived_estimates')) {
                $collection->setConditions('status IN (?) AND is_trashed = ?', [Estimate::WON, Estimate::LOST], false);
                $collection->setOrderBy('updated_on DESC');

                $bits = explode('_', $collection_name);
                $collection->setPagination(array_pop($bits), 30);
            } else {
                throw new InvalidParamError('collection_name', $collection_name);
            }
        }

        return $collection;
    }

    /**
     * Return private notes for estimates.
     */
    public static function getPrivateNotes(): array
    {
        $result = [];

        if ($rows = DB::execute('SELECT id, private_note FROM estimates')) {
            foreach ($rows as $row) {
                $result[$row['id']] = (string) $row['private_note'];
            }
        }

        return $result;
    }

    public static function getEstimatePdfName(Estimate $estimate): string
    {
        return 'estimate-' . $estimate->getName() . '.pdf';
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Estimate
    {
        /** @var VariableProcessorInterface $variable_processor */
        $variable_processor = AngieApplication::getContainer()
            ->get(VariableProcessorFactoryInterface::class)
                ->createForInvoice();

        $attributes = AngieApplication::getContainer()
            ->get(InvoiceAttributesProcessorInterface::class)
                ->prepareAttributesForNewEstimate($attributes, $variable_processor);

        try {
            DB::beginWork('Begin: create new estimate @ ' . __CLASS__);

            $estimate = parent::create($attributes, false, false);

            if ($estimate instanceof Estimate && $save) {
                $estimate->dontUpdateSearchIndexOnNextSave();
                $estimate->save();

                $estimate->addItemsFromAttributes($attributes, $variable_processor);

                AngieApplication::search()->add($estimate);
            }

            DB::commit('Done: create new estimate @ ' . __CLASS__);

            return $estimate;
        } catch (Exception $e) {
            DB::rollback('Rollback: create new estimate @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * @param DataObject|Estimate $instance
     */
    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): Estimate
    {
        $notify_on_total_update = $instance->isSent();

        if (array_key_exists('notify_on_total_update', $attributes)) {
            $notify_on_total_update = $instance->isSent() && $attributes['notify_on_total_update'];
            unset($attributes['notify_on_total_update']);
        }

        $current_total = $instance->getTotal();

        try {
            DB::beginWork('Begin: update the estimate @ ' . __CLASS__);

            $instance->dontUpdateSearchIndexOnNextSave();

            parent::update($instance, $attributes, $save);
            $instance->updateItemsFromAttributes($attributes);

            AngieApplication::search()->update($instance);

            DB::commit('Done: update the estimate @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: update the estimate @ ' . __CLASS__);
            throw $e;
        }

        if ($notify_on_total_update && $current_total != $instance->getTotal()) {
            AngieApplication::notifications()->notifyAbout('invoicing/estimate_updated', $instance, AngieApplication::authentication()->getLoggedUser())
                ->setOldTotal($current_total)
                ->sendToUsers($instance->getRecipientInstances(), true);
        }

        return $instance;
    }

    public static function canAdd(User $user): bool
    {
        return $user->isFinancialManager();
    }

    /**
     * Method use to set update_on field to now on all estimates.
     */
    public static function bulkUpdateOn(): void
    {
        DB::execute('UPDATE estimates SET updated_on = UTC_TIMESTAMP()');
        self::clearCache();
    }
}
