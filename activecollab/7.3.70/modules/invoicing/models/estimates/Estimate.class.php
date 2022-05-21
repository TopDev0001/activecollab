<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;
use Angie\Search\SearchDocument\SearchDocumentInterface;

class Estimate extends BaseEstimate
{
    const DRAFT = 'draft';
    const SENT = 'sent';
    const WON = 'won';
    const LOST = 'lost';

    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'status',
            ]
        );
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'hash' => $this->getHash(),
                'status' => $this->getStatus(),
                'public_url' => $this->getPublicUrl(),
            ]
        );
    }

    public function getRoutingContext(): string
    {
        return 'estimate';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'estimate_id' => $this->getId(),
        ];
    }

    /**
     * @return IUser
     */
    public function getSentBy()
    {
        return $this->getUserFromFieldSet('sent_by');
    }

    /**
     * Set sent by instance.
     *
     * @param  User|null $value
     * @return User|null
     */
    protected function setSentBy($value)
    {
        return $this->setUserFromFieldSet($value, 'sent_by', true, false);
    }

    /**
     * Sent an estimate to the client.
     *
     * @param  User    $sender
     * @param  IUser[] $recipients
     * @param  string  $subject
     * @param  string  $message
     * @return $this
     */
    public function &send($sender, $recipients, $subject, $message)
    {
        if ($recipients && is_foreachable($recipients)) {
            $recipient_addresses = [];

            foreach ($recipients as $recipient) {
                if ($recipient->getDisplayName()) {
                    $recipient_addresses[] = $recipient->getDisplayName() . ' <' . $recipient->getEmail() . '>';
                } else {
                    $recipient_addresses[] = $recipient->getEmail();
                }
            }

            $this->setRecipients(implode(', ', $recipient_addresses));
        }

        $this->setEmailFrom($sender);
        $this->setEmailSubject($subject);
        $this->setEmailBody($message);

        if ($this->getSentOn() === null) {
            $this->setSentOn(DateTimeValue::now());
            $this->setSentBy($sender);
        }

        if ($this->getStatus() === self::DRAFT) {
            $this->setStatus(self::SENT);
        }

        $this->save();

        // Forget object in the pool now that it is updated, so we get a fresh instance in the email templates.
        DataObjectPool::forget(
            Estimate::class,
            $this->getId()
        );

        AngieApplication::log()->event(
            'estimate_sent',
            'Estimate #{estimate_id} is sent by {sender} to {recipients}',
            [
                'estimate_id' => $this->getId(),
                'sender' => $sender->getEmail(),
                'recipients' => $this->getRecipients(),
            ]
        );

        /** @var SendEstimateNotification $notification */
        $notification = AngieApplication::notifications()->notifyAbout('invoicing/send_estimate', $this, $sender);
        $notification
            ->setCustomSubject($subject)
            ->setCustomMessage($message)
            ->sendToUsers($recipients, true);

        return $this;
    }

    public function getSearchDocument(): SearchDocumentInterface
    {
        return new EstimateSearchDocument($this);
    }

    // ---------------------------------------------------
    //  URL-s
    // ---------------------------------------------------

    /**
     * Return public page URL.
     *
     * @return string
     */
    public function getPublicUrl()
    {
        return '#';
    }

    /**
     * Return public page URL.
     *
     * @return string
     */
    public function getPublicPdfUrl()
    {
        return '#';
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    public function validate(ValidationErrors & $errors)
    {
        if (!$this->validatePresenceOf('name')) {
            $errors->fieldValueIsRequired('name');
        }

        parent::validate($errors);
    }

    /**
     * Cached project that has been created based on this estimate.
     *
     * @var bool|Project
     */
    private $project = false;

    /**
     * Get a project based on this estimate (assume last created one).
     */
    public function &getProject()
    {
        if ($this->project === false) {
            $this->project = Projects::findOne(
                [
                    'conditions' => [
                            'based_on_type = ? AND based_on_id = ?',
                            get_class($this),
                            $this->getId(),
                        ],
                    'order' => 'created_on DESC',
                ]
            );
        }

        return $this->project;
    }

    /**
     * Check if estimate is draft.
     *
     * @return bool
     */
    public function isDraft()
    {
        return $this->getStatus() == self::DRAFT;
    }

    /**
     * Returns true if $this estimate is being sent.
     *
     * @return bool
     */
    public function isSent()
    {
        return $this->getSentOn() !== null;
    }

    /**
     * Returns true if $this estimate is won.
     *
     * @return bool
     */
    public function isWon()
    {
        return $this->getStatus() == self::WON;
    }

    /**
     * Returns true if $this estimate is being lost.
     *
     * @return bool
     */
    public function isLost()
    {
        return $this->getStatus() == self::LOST;
    }

    public function save()
    {
        if ($this->getSecondTaxIsCompound() === null) {
            $this->setSecondTaxIsCompound(false);
        }

        parent::save();
    }

    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Removing estimate @ ' . __CLASS__);

            parent::delete($bulk);

            $project_ids = DB::executeFirstColumn(
                'SELECT `id` FROM `projects` WHERE `based_on_type` = ? AND `based_on_id` = ?',
                Estimate::class,
                $this->getId()
            );

            if (!empty($project_ids)) {
                DB::execute(
                    'UPDATE `projects` SET `based_on_type` = ?, `based_on_id` = ?, `updated_on` = UTC_TIMESTAMP() WHERE `id` IN (?)',
                    null,
                    0,
                    $project_ids
                );

                Projects::clearCacheFor($project_ids);
            }

            DB::commit('Estimate removed @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to remove estimate @ ' . __CLASS__);

            throw $e;
        }
    }

    // ---------------------------------------------------
    //  Invoice based on
    // ---------------------------------------------------
    /**
     * Create new invoice instance based on parent object.
     *
     * @param  string     $number
     * @param  string     $client_address
     * @param  array|null $additional
     * @return Invoice
     */
    public function createInvoice($number, Company $client = null, $client_address = null, $additional = null, IUser $user = null)
    {
        $invoice = new Invoice();

        try {
            DB::beginWork('Begin: create invoice from estimate @ ' . __CLASS__);

            $invoice->setNumber($number);
            $invoice->setBasedOn($this);
            $invoice->setDueOn(new DateValue());

            if (isset($additional['project_id']) && $additional['project_id']) {
                $invoice->setProjectId($additional['project_id']);
            }

            $invoice->setCompanyId($client->getId());
            //$invoice->setCompanyName($client->getName());
            $invoice->setCompanyAddress($client_address);

            $invoice->setCurrencyId($this->getCurrency()->getId());
            $invoice->setLanguageId($this->getLanguageId());
            $invoice->setNote(array_var($additional, 'note', ''));
            $invoice->setPrivateNote(array_var($additional, 'private_note', ''));
            $invoice->setPurchaseOrderNumber($additional['purchase_order_number']);

            if (!$this->isWon()) {
                $this->setStatus(self::WON);
                $this->save();

                $subscribers = $this->getSubscribers();
                if ($subscribers && is_foreachable($subscribers)) {
                    foreach ($subscribers as $k => $subscriber) {
                        if ($subscriber->getId() == $user->getId()) {
                            unset($subscribers[$k]); // exclude a user who have won the estimate
                        }
                    }
                }
            }

            $items = $this->prepareItemsForInvoice();

            if ($items && is_foreachable($items)) {
                $this->commitInvoiceItems($items, $invoice); // Save, add items, recalculate
            } else {
                throw new Error('Invoice must have at least one item.');
            }

            DB::commit('Done: create invoice from estimate @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: create invoice from estimate @ ' . __CLASS__);

            throw $e;
        }

        return $invoice;
    }

    /**
     * Return items preview based on given settings.
     *
     * @param  array $settings
     * @return mixed
     */
    public function previewInvoiceItems($settings = null, IUser $user = null)
    {
        return $this->prepareItemsForInvoice();
    }

    /**
     * Create items for invoice.
     *
     * @return mixed
     */
    protected function prepareItemsForInvoice()
    {
        $result = [];

        if ($items = $this->getItems()) {
            foreach ($items as $item) {
                $result[] = [
                    'description' => $item->getDescription(),
                    'unit_cost' => $item->getUnitCost(),
                    'quantity' => $item->getQuantity(),
                    'first_tax_rate_id' => $item->getFirstTaxRateId(),
                    'second_tax_rate_id' => $item->getSecondTaxRateId(),
                    'total' => $item->getTotal(),
                    'subtotal' => $item->getSubtotal(),
                ];
            }
        }

        return $result;
    }
}
