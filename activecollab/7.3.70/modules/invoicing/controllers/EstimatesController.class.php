<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\FileDownload\FileDownload;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Estimates controller.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage controllers
 */
class EstimatesController extends AuthRequiredController
{
    /**
     * Selected estimate.
     *
     * @var Estimate
     */
    protected $active_estimate;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($user instanceof User && $user->isFinancialManager()) {
            $this->active_estimate = DataObjectPool::get('Estimate', $request->getId('estimate_id'));

            if (empty($this->active_estimate)) {
                $this->active_estimate = new Estimate();
            }
        } else {
            return Response::NOT_FOUND;
        }
    }

    /**
     * List active estimates.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return Estimates::prepareCollection('active_estimates', $user);
    }

    /**
     * List archived estimates.
     *
     * @return ModelCollection
     */
    public function archive(Request $request, User $user)
    {
        return Estimates::prepareCollection('archived_estimates_page_' . $request->getPage(), $user);
    }

    /**
     * Show private notes for active estimates.
     *
     * @return array
     */
    public function private_notes()
    {
        return Estimates::getPrivateNotes();
    }

    /**
     * Show single estimate.
     *
     * @return int|Estimate
     */
    public function view(Request $request, User $user)
    {
        return $this->active_estimate->isLoaded() ? AccessLogs::logAccess($this->active_estimate, $user) : Response::NOT_FOUND;
    }

    /**
     * Create a new estimate.
     *
     * @return Estimate|int
     */
    public function add(Request $request, User $user)
    {
        return Estimates::canAdd($user) ? Estimates::create($request->post()) : Response::NOT_FOUND;
    }

    /**
     * Update an estimate.
     *
     * @return bool|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_estimate->isLoaded() && $this->active_estimate->canEdit($user) ? Estimates::update($this->active_estimate, $request->put()) : Response::NOT_FOUND;
    }

    /**
     * Issue the estimate.
     *
     * @return Estimate|int
     */
    public function send(Request $request, User $user)
    {
        if ($this->active_estimate->isLoaded()) {
            [$recipients, $subject, $message] = $this->processParametersForSendAction($request);

            if (empty($recipients)) {
                return Response::BAD_REQUEST;
            }

            if (AngieApplication::isOnDemand()) {
                $filtered_estimate = OnDemand::filterInvoiceRecipients($this->active_estimate, $recipients);

                if ($filtered_estimate instanceof IInvoice) {
                    return $filtered_estimate;
                }
            }

            return $this->active_estimate->send($user, $recipients, $subject, $message);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Process PUT parameters for send() action.
     *
     * @return array
     */
    private function processParametersForSendAction(Request $request)
    {
        $put = $request->put();

        $recipients = [];

        if (isset($put['recipients'])) {
            if (is_string($put['recipients'])) {
                $recipients = Users::findByAddressList($put['recipients']);
            } elseif (is_foreachable($put['recipients'])) {
                foreach ($put['recipients'] as $email) {
                    [$name, $email] = email_split($email);
                    if (is_valid_email($email)) {
                        $recipient = Users::findByEmail($email, true);
                        if ($recipient instanceof User) {
                            $recipients[] = $recipient;
                        } else {
                            $recipients[] = new AnonymousUser(null, $email);
                        }
                    }
                }
            }
        }

        $subject = isset($put['subject']) && $put['subject'] ? trim($put['subject']) : null;
        $message = isset($put['message']) && $put['message'] ? trim($put['message']) : null;

        return [$recipients, $subject, $message];
    }

    /**
     * Export estimate to PDF.
     *
     * @return FileDownload|int
     */
    public function export(Request $request, User $user)
    {
        if ($this->active_estimate->isLoaded() && $this->active_estimate->canView($user)) {
            return new FileDownload($this->active_estimate->exportToFile(), 'application/pdf', Estimates::getEstimatePdfName($this->active_estimate));
        }

        return Response::NOT_FOUND;
    }

    /**
     * Duplicate selected estimate.
     *
     * @return int|string
     */
    public function duplicate(Request $request, User $user)
    {
        if ($this->active_estimate->isLoaded() && Estimates::canAdd($user)) {
            return $this->active_estimate->copy(true);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Delete an estimate.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_estimate->isLoaded() && $this->active_estimate->canDelete($user) ? Estimates::scrap($this->active_estimate) : Response::NOT_FOUND;
    }
}
