<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', SystemModule::NAME);

class RecurringProfilesController extends AuthRequiredController
{
    /**
     * Selected invoice.
     *
     * @var RecurringProfile
     */
    protected $active_recurring_profile;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($user instanceof User && $user->isFinancialManager()) {
            $this->active_recurring_profile = DataObjectPool::get(
                RecurringProfile::class,
                $request->getId('recurring_profile_id')
            );

            if (empty($this->active_recurring_profile)) {
                $this->active_recurring_profile = new RecurringProfile();
            }
        } else {
            return Response::NOT_FOUND;
        }

        return null;
    }

    /**
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return RecurringProfiles::prepareCollection('active_profiles', $user);
    }

    /**
     * @return ModelCollection
     */
    public function archive(Request $request, User $user)
    {
        return RecurringProfiles::prepareCollection('expired_profiles_page_' . $request->getPage(), $user);
    }

    /**
     * @return int[]
     */
    public function trigger(Request $request)
    {
        $specified_date = AngieApplication::isInDevelopment() ? $request->post('date') : null;

        $invoices = AngieApplication::recurringInvoicesDispatcher()->trigger(
            $specified_date ? DateValue::makeFromString($specified_date) : DateValue::now()
        );

        return array_map(
            function (Invoice $invoice) {
                return $invoice->getId();
            },
            $invoices
        );
    }

    /**
     * @return int|RecurringProfile
     */
    public function view()
    {
        if (!$this->active_recurring_profile->isLoaded()) {
            return Response::NOT_FOUND;
        }

        return $this->active_recurring_profile;
    }

    /**
     * @return RecurringProfile|int|DataObject
     */
    public function add(Request $request, User $user)
    {
        return RecurringProfiles::canAdd($user) ? RecurringProfiles::create($request->post()) : Response::NOT_FOUND;
    }

    /**
     * @return RecurringProfile|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_recurring_profile->isLoaded() && $this->active_recurring_profile->canEdit($user)
            ? RecurringProfiles::update($this->active_recurring_profile, $request->put())
            : Response::NOT_FOUND;
    }

    /**
     * @return RecurringProfile|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_recurring_profile->isLoaded() && $this->active_recurring_profile->canDelete($user)
            ? RecurringProfiles::scrap($this->active_recurring_profile)
            : Response::NOT_FOUND;
    }

    /**
     * Retrurn next trigger date.
     *
     * @return array|int
     */
    public function next_trigger_on()
    {
        return $this->active_recurring_profile->isLoaded()
            ? ['next_trigger_on' => $this->active_recurring_profile->getNextTriggerOn()]
            : Response::NOT_FOUND;
    }
}
