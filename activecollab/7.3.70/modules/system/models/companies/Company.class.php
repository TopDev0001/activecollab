<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use Angie\Search\SearchDocument\SearchDocumentInterface;

class Company extends BaseCompany
{
    public const DEFAULT_COMPANY_NAME = 'Owner Company';

    protected ?array $protect = [
        'is_owner',
    ];

    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'address',
                'homepage_url',
                'phone',
                'note',
                'currency_id',
            ],
        );
    }

    public function getSearchFields(): array
    {
        return array_merge(
            parent::getSearchFields(),
            [
                'address',
                'homepage_url',
                'phone',
                'note',
            ],
        );
    }

    /**
     * Return users that belongs to $this company.
     *
     * @param  array  $ids
     * @param  int    $min_state
     * @return User[]
     */
    public function getUsers($ids = [], $min_state = STATE_VISIBLE)
    {
        return Users::findByCompany($this, $ids, $min_state);
    }

    /**
     * Return active company projects.
     *
     * @return DBResult|Project[]
     */
    public function getActiveProjects()
    {
        return Projects::find(
            [
                'conditions' => [
                    'company_id = ? AND completed_on IS NULL AND is_trashed = ?',
                    $this->getId(),
                    false,
                ],
            ],
        );
    }

    /**
     * Return all company projects.
     *
     * @return DBResult|Project[]
     */
    public function getAllProjects()
    {
        return Projects::find(
            [
                'conditions' => [
                    'company_id = ?',
                    $this->getId(),
                ],
            ],
        );
    }

    /**
     * Describe for feather.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['address'] = $this->getAddress();
        $result['phone'] = $this->getPhone();
        $result['homepage_url'] = $this->getHomepageUrl();
        $result['tax_id'] = $this->getTaxId();
        $result['currency_id'] = $this->getCurrencyId();
        $result['is_owner'] = $this->getIsOwner();
        $result['has_note'] = $this->getNote() && !empty(trim($this->getNote()));

        return $result;
    }

    /**
     * Describe single.
     */
    public function describeSingleForFeather(array &$result)
    {
        parent::describeSingleForFeather($result);

        $result['hourly_rates'] = $this->getHourlyRates();
        $result['active_projects_count'] = DB::executeFirstCell(
            'SELECT COUNT(`id`) AS "row_count" FROM `projects` WHERE `company_id` = ? AND `is_trashed` = ? AND `completed_on` IS NULL',
            $this->getId(),
            false,
        );
    }

    public function getSearchDocument(): SearchDocumentInterface
    {
        return new CompanySearchDocument($this);
    }

    // ---------------------------------------------------
    //  Interface implementations
    // ---------------------------------------------------

    public function getRoutingContext(): string
    {
        return 'company';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'company_id' => $this->getId(),
        ];
    }

    public function setMembers(iterable $users, array $additional = null): void
    {
        $other_company_ids = [];

        if (is_iterable($users) && !empty($users)) {
            foreach ($users as $user) {
                if ($user->getCompanyId() != $this->getId()) {
                    $other_company_ids[] = $user->getCompanyId();
                }
            }
        }

        try {
            DB::beginWork('Setting users @ ' . __CLASS__);

            parent::setMembers($users, $additional);

            if (!empty($other_company_ids)) {
                $companies = Companies::findByIds($other_company_ids);

                if (!empty($companies)) {
                    foreach ($companies as $company) {
                        $company->touch();
                    }
                }
            }

            DB::commit('Users set @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to set users @ ' . __CLASS__);

            throw $e;
        }
    }

    public function moveToArchive(User $by, $bulk = false)
    {
        try {
            DB::beginWork('Moving company to archive @ ' . __CLASS__);

            parent::moveToArchive($by, $bulk);

            if ($users = $this->getUsers([], STATE_TRASHED)) {
                foreach ($users as $user) {
                    $user->moveToArchive($by, true);
                }
            }

            if ($all_projects = $this->getAllProjects()) {
                foreach ($all_projects as $project) {
                    $project->setCompanyId(AngieApplication::getContainer()->get(OwnerCompanyResolverInterface::class)->getId());
                    $project->save();
                }
            }

            DB::commit('Failed to move company to archive @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to archive company @ ' . __CLASS__);

            throw $e;
        }

        Users::clearCache();
    }

    /**
     * Restore from trash.
     *
     * @param  bool      $bulk
     * @throws Exception
     */
    public function restoreFromArchive($bulk = false)
    {
        try {
            DB::beginWork('Restoring company from archive @ ' . __CLASS__);

            parent::restoreFromArchive($bulk);

            if ($users = $this->getUsers([], STATE_TRASHED)) {
                foreach ($users as $user) {
                    $user->restoreFromArchive(true);
                }
            }

            DB::commit('Failed to restore company from archive @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to restore company from archive @ ' . __CLASS__);

            throw $e;
        }

        Users::clearCache();
    }

    /**
     * Move to trash.
     *
     * @param  bool      $bulk
     * @throws Exception
     */
    public function moveToTrash(User $by = null, $bulk = false)
    {
        try {
            DB::beginWork('Moving company to trash @ ' . __CLASS__);

            parent::moveToTrash($by, $bulk);

            if ($projects = $this->getAllProjects()) {
                foreach ($projects as $project) {
                    $project->setCompanyId(AngieApplication::getContainer()->get(OwnerCompanyResolverInterface::class)->getId());
                    $project->save();
                }
            }

            if ($users = $this->getUsers([], STATE_TRASHED)) {
                foreach ($users as $user) {
                    $user->moveToTrash($by, true);
                }
            }

            DB::commit('Failed to move company to trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to trash company @ ' . __CLASS__);

            throw $e;
        }

        Users::clearCache();
    }

    /**
     * Restore from trash.
     *
     * @param  bool      $bulk
     * @throws Exception
     */
    public function restoreFromTrash($bulk = false)
    {
        try {
            DB::beginWork('Restoring company from trash @ ' . __CLASS__);

            parent::restoreFromTrash($bulk);

            if ($users = $this->getUsers([], STATE_TRASHED)) {
                foreach ($users as $user) {
                    $user->restoreFromTrash(true);
                }
            }

            DB::commit('Failed to restore company from trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to restore company from trash @ ' . __CLASS__);

            throw $e;
        }

        Users::clearCache();
    }

    public function getHistoryFieldRenderers(): array
    {
        $renderers = parent::getHistoryFieldRenderers();

        $renderers['note'] = function ($old_value, $new_value, Language $language) {
            if ($new_value && $old_value) {
                return lang('Note updated', null, true, $language);
            } elseif ($new_value) {
                return lang('Note added', null, true, $language);
            } elseif ($old_value) {
                return lang('Note removed', null, true, $language);
            }
        };
        $renderers['address'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Company address changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Company address Name set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Company address set to empty value', null, true, $language);
                }
            }
        };
        $renderers['homepage_url'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Company website url changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Company website url set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Company website url set to empty value', null, true, $language);
                }
            }
        };
        $renderers['phone'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Company phone changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Company phone set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Company phone set to empty value', null, true, $language);
                }
            }
        };
        $renderers['currency_id'] = function ($old_value, $new_value, Language $language) {
            $new_currency = DataObjectPool::get('Currency', $new_value);
            $old_currency = DataObjectPool::get('Currency', $old_value);

            if ($new_currency instanceof Currency) {
                if ($old_currency instanceof Currency) {
                    return lang('Currency changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_currency->getCode(), 'new_value' => $new_currency->getCode()], true, $language);
                } else {
                    return lang('Currency set to <b>:new_value</b>', ['new_value' => $new_currency->getCode()], true, $language);
                }
            } else {
                if ($old_currency instanceof Currency || is_null($new_currency)) {
                    return lang('Currency set to empty value', null, true, $language);
                }
            }
        };

        return $renderers;
    }

    public function canView(User $user): bool
    {
        return in_array($this->getId(), $user->getVisibleCompanyIds());
    }

    /**
     * Returns true if $user can see note value for this company.
     *
     * @return bool
     */
    public function canSeeNote(User $user)
    {
        return Companies::canSeeNotes($user);
    }

    public function canEdit(User $user): bool
    {
        return $user->isPowerUser();
    }

    public function canDelete(User $user): bool
    {
        if ($this->getIsOwner() || $user->getCompanyId() == $this->getId()) {
            return false;  // Owner company cannot be deleted. Also, users cannot delete companies that they belong to
        }

        if (Companies::isLastOwnerInCompany($this)) {
            return false; // Can't delete company that has last owner
        }

        if ($user->isOwner()) {
            return true;
        } else {
            if ($user->isPowerUser()) {
                return !DB::executeFirstCell('SELECT COUNT(id) FROM projects WHERE company_id = ? AND completed_on IS NULL', $this->getId());
            }
        }

        return false;
    }

    public function canTrash(User $user): bool
    {
        if ($this->getIsOwner() || $user->getCompanyId() == $this->getId()) {
            return false;  // Owner company cannot be trashed. Also, users cannot trash companies that they belong to
        }

        if (Companies::hasOwners($this)) {
            return false; // if company have at least one owners shouldn't be trashed
        }

        return parent::canTrash($user);
    }

    public function canArchive(User $user): bool
    {
        if ($this->getIsOwner() || $user->getCompanyId() == $this->getId()) {
            return false;  // Owner company cannot be archived. Also, users cannot archive companies that they belong to
        }

        if (Companies::hasOwners($this)) {
            return false; // if company have at least one owners shouldn't be archived
        }

        return parent::canArchive($user);
    }

    // ---------------------------------------------------
    //  SYSTEM
    // ---------------------------------------------------

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors &$errors)
    {
        if ($this->validatePresenceOf('name')) {
            $this->validateUniquenessOf('name') or $errors->fieldValueNeedsToBeUnique('name');
        } else {
            $errors->fieldValueIsRequired('name');
        }

        if ($this->getIsOwner() && ($this->isModifiedField('is_archived') || $this->isModifiedField('is_trashed'))) {
            $errors->addError("Owner company can't be archived, trashed or deleted");
        }

        parent::validate($errors);
    }

    /**
     * Clear cache on save.
     */
    public function save()
    {
        $name_changed = $this->isModifiedField('name');
        $note_changed = $this->isModifiedField('note');

        parent::save();

        if ($name_changed) {
            AngieApplication::cache()->remove(['models', 'companies', 'id_name_map']);
        }

        if ($note_changed) {
            AngieApplication::cache()->remove(['models', 'companies', 'id_note_map']);
        }
    }

    /**
     * Delete this company from database.
     *
     * @param  bool      $bulk
     * @throws Exception
     */
    public function delete($bulk = false)
    {
        if ($this->getIsOwner()) {
            throw new NotImplementedError(__METHOD__, 'This method is not available for owner company');
        }

        try {
            DB::beginWork('Deleting company @ ' . __CLASS__);

            if ($project_ids = DB::executeFirstColumn('SELECT id FROM projects WHERE company_id = ?', $this->getId())) {
                DB::execute(
                    'UPDATE projects SET company_id = ?, updated_on = UTC_TIMESTAMP() WHERE company_id = ?',
                    AngieApplication::getContainer()
                        ->get(OwnerCompanyResolverInterface::class)
                            ->getId(),
                    $this->getId(),
                ); // Reset company ID for projects
            }

            if ($invoice_ids = DB::executeFirstColumn('SELECT id FROM invoices WHERE company_id = ?', $this->getId())) {
                DB::execute('UPDATE invoices SET company_id = ?, updated_on = UTC_TIMESTAMP() WHERE company_id = ?', 0, $this->getId()); // Reset company ID for invoices
            }

            parent::delete($bulk);

            DB::commit('Company deleted @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to delete company @ ' . __CLASS__);

            throw $e;
        }

        Companies::clearCache();

        if ($project_ids) {
            Projects::clearCacheFor($project_ids);
        }

        if ($invoice_ids) {
            Invoices::clearCacheFor($invoice_ids);
        }
    }

    protected function getSearchEngine()
    {
        return AngieApplication::search();
    }
}
