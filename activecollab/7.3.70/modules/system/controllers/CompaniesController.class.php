<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\FileDownload\FileDownload;

AngieApplication::useController('auth_required', SystemModule::NAME);

class CompaniesController extends AuthRequiredController
{
    /**
     * Selected company.
     *
     * @var Company
     */
    protected $active_company;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_company = DataObjectPool::get('Company', $request->getId('company_id'));

        if (empty($this->active_company)) {
            $this->active_company = new Company();
        }
    }

    /**
     * Show active companies.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return Companies::prepareCollection(Companies::ACTIVE, $user);
    }

    /**
     * Show all users.
     *
     * @return ModelCollection
     */
    public function all(Request $request, User $user)
    {
        return Companies::prepareCollection(Companies::ALL, $user);
    }

    /**
     * Show archived companies.
     *
     * @return ModelCollection
     */
    public function archive(Request $request, User $user)
    {
        return Companies::prepareCollection(Companies::ARCHIVED, $user);
    }

    /**
     * List company notes.
     *
     * @return array|int
     */
    public function notes(Request $request, User $user)
    {
        return Companies::canSeeNotes($user) ? Companies::getIdNoteMap() : Response::NOT_FOUND;
    }

    /**
     * Show company details.
     *
     * @return Company|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_company->isLoaded() && $this->active_company->canView($user) ? $this->active_company : Response::NOT_FOUND;
    }

    /**
     * Export company vCard.
     *
     * @return FileDownload|int
     */
    public function export(Request $request, User $user)
    {
        //        if ($this->active_company->isLoaded() && $this->active_company->canView($user)) {
//            $file = $this->active_company->toVCard($request->get('include_users'));

//            if (is_file($file)) {
//                return new FileDownload($file, 'text/x-vcard', $this->active_company->getName() . '.vcf', true);
//            }

//            return Response::OPERATION_FAILED;
//        }

        return Response::NOT_FOUND;
    }

    /**
     * List company projects.
     *
     * @return int|ModelCollection
     */
    public function projects(Request $request, User $user)
    {
        if ($this->active_company->isLoaded() && $this->active_company->canView($user)) {
            return Projects::prepareCollection('company_projects_' . $this->active_company->getId() . '_page_' . $request->getPage(), $user);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Return project names that belong to active company.
     *
     * @return array
     */
    public function project_names(Request $request, User $user)
    {
        return Projects::getIdNameMapFor($user, ['projects.company_id = ?', $this->active_company->getId()]);
    }

    /**
     * List company invoices.
     *
     * @return array
     */
    public function invoices(Request $request, User $user)
    {
        if ($this->active_company->isLoaded() && !$this->active_company->getIsOwner() && $user->isFinancialManager()) {
            $invoices = [];

            $invoices_filter = new InvoicesFilter();
            $invoices_filter->filterByClientId($this->active_company->getId());
            $invoices_filter->setIncludeCreditInvoices(true);

            $results = $invoices_filter->run($user, ['is_trashed' => false]);

            if (isset($results['all'])) {
                $invoices = array_values($results['all']['invoices']);
                foreach ($invoices as $index => $invoice) {
                    $invoices[$index]['class'] = 'Invoice';
                    $invoices[$index]['url_path'] = '/invoices/' . $invoice['id'];
                    $invoices[$index]['rounded_total'] = $this->calculateInvoiceRoundedTotal($invoices[$index]['currency_id'], $invoices[$index]['total']);
                }
            }

            return $invoices;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Create a new company.
     *
     * @return Company|int
     */
    public function add(Request $request, User $user)
    {
        if (Companies::canAdd($user)) {
            $data = $request->post();

            if (!Companies::canSeeNotes($user) && isset($data['note'])) {
                unset($data['note']);
            }

            return Companies::create($data);
        }

        return Response::FORBIDDEN;
    }

    /**
     * Edit Company Info.
     *
     * @return Company|int
     */
    public function edit(Request $request, User $user)
    {
        if ($this->active_company->isLoaded() && $this->active_company->canEdit($user)) {
            $data = $request->put();

            if (!Companies::canSeeNotes($user) && isset($data['note'])) {
                unset($data['note']);
            }

            return Companies::update($this->active_company, $data);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Delete company.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_company->isLoaded() && $this->active_company->canDelete($user) ? Companies::scrap($this->active_company) : Response::NOT_FOUND;
    }

    /**
     * Return invoice rounded total.
     *
     * @param  int        $currency_id
     * @param  int        $total
     * @return Currencies
     */
    private function calculateInvoiceRoundedTotal($currency_id, $total)
    {
        $currency = DataObjectPool::get('Currency', $currency_id);

        if (!($currency instanceof Currency)) {
            $currency = Currencies::getDefault();
        }

        return Currencies::roundDecimal($total, $currency);
    }
}
