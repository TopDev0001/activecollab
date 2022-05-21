<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Calcinai\OAuth2\Client\Provider\Xero;
use OAuth2\GrantType\AuthorizationCode;
use XeroPHP\Application;
use XeroPHP\Models\Accounting\Account;
use XeroPHP\Models\Accounting\Contact;
use XeroPHP\Models\Accounting\Item;
use XeroPHP\Remote\Exception\ForbiddenException;
use XeroPHP\Remote\Exception\UnauthorizedException;

class XeroIntegration extends Integration
{
    const REMOTE_DATA_CACHE_TTL = 1800;
    const REVOCATION_ENDPOINT = 'https://identity.xero.com/connect/revocation';

    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        return !empty($this->hasValidAccess());
    }

    /**
     * Return true if access to Xero is valid.
     *
     * @return bool|null
     */
    public function hasValidAccess()
    {
        return $this->getAccessToken();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Xero';
    }

    public function getShortName(): string
    {
        return 'xero';
    }

    /**
     * Return short integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang('Create Xero invoices from billable time and expenses');
    }

    /**
     * Get group of this integration.
     *
     * @return string
     */
    public function getGroup()
    {
        return 'accounting';
    }

    /**
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->getAdditionalProperty('access_token');
    }

    /**
     * @param  string $access_token
     * @return mixed
     */
    public function setAccessToken($access_token)
    {
        return $this->setAdditionalProperty('access_token', $access_token);
    }

    public function getRefreshToken()
    {
        return $this->getAdditionalProperty('refresh_token');
    }

    public function setRefreshToken(string $refresh_token)
    {
        return $this->setAdditionalProperty('refresh_token', $refresh_token);
    }

    public function getTokenExpiresTimestamp()
    {
        return $this->getAdditionalProperty('access_token_expires_in');
    }

    public function setTokenExpiresTimestamp(int $expires_in)
    {
        return $this->setAdditionalProperty('access_token_expires_in', $expires_in);
    }

    /**
     * @return string|null
     */
    public function getClientId()
    {
        return AngieApplication::isOnDemand() && (string) getenv('ACTIVECOLLAB_XERO_CLIENT_ID')
            ? (string) getenv('ACTIVECOLLAB_XERO_CLIENT_ID')
            : $this->getAdditionalProperty('client_id');
    }

    /**
     * @param  string $client_id
     * @return mixed
     */
    public function setClientId($client_id)
    {
        return $this->setAdditionalProperty('client_id', $client_id);
    }

    /**
     * @return string|null
     */
    public function getClientSecret()
    {
        return AngieApplication::isOnDemand() && (string) getenv('ACTIVECOLLAB_XERO_CLIENT_SECRET')
            ? (string) getenv('ACTIVECOLLAB_XERO_CLIENT_SECRET')
            : $this->getAdditionalProperty('client_secret');
    }

    /**
     * @param  string $client_secret
     * @return mixed
     */
    public function setClientSecret($client_secret)
    {
        return $this->setAdditionalProperty('client_secret', $client_secret);
    }

    public function getXeroTenantId()
    {
        return $this->getAdditionalProperty('xero_tenant_id');
    }

    public function setXeroTenantId(string $id)
    {
        return $this->setAdditionalProperty('xero_tenant_id', $id);
    }

    /**
     * Return if token has expired.
     *
     * @return bool
     */
    public function isTokenExpired()
    {
        return $this->getTokenExpiresTimestamp() < DateTimeValue::now()->getTimestamp() + 100;
    }

    private function getCallbackUrl(): string
    {
        if (!AngieApplication::isOnDemand()) {
            return ROOT_URL . '/integrations/xero';
        }

        return SHEPHERD_URL . '/api/v2/xero-oauth';
    }

    /**
     * @return mixed
     */
    public function getOrganizationName()
    {
        return $this->getAdditionalProperty('xero_organization_name');
    }

    /**
     * @param $organization_name
     */
    public function setOrganizationName($organization_name)
    {
        $this->setAdditionalProperty('xero_organization_name', $organization_name);
    }

    /**
     * @return mixed (Xero Account Code)
     */
    public function getDefaultXeroAccount()
    {
        return $this->getAdditionalProperty('default_xero_account');
    }

    /**
     * Set default xero account (Xero Account Code).
     *
     * @param string $value
     */
    public function setDefaultXeroAccount($value)
    {
        $this->setAdditionalProperty('default_xero_account', $value);
    }

    /**
     * Return value how taxes are represented.
     *
     * @return string
     */
    public function getShowTaxesAs()
    {
        return $this->getAdditionalProperty('show_taxes_as', \XeroPHP\Models\Accounting\Invoice::LINEAMOUNT_TYPE_EXCLUSIVE);
    }

    /**
     * Set hove taxes are represented.
     *
     * @param string $value
     */
    public function setShowTaxesAs($value)
    {
        $this->setAdditionalProperty('show_taxes_as', $value);
    }

    /**
     * Return default item code.
     *
     * @return string
     */
    public function getDefaultItemCode()
    {
        return $this->getAdditionalProperty('default_item_code');
    }

    /**
     * Set default item code.
     *
     * @param string $value
     */
    public function setDefaultItemCode($value)
    {
        $this->setAdditionalProperty('default_item_code', $value);
    }

    public function getAuthorizationUrl()
    {
        $data = [
            'clientId' => $this->getClientId(),
            'clientSecret' => $this->getClientSecret(),
            'redirectUri' => $this->getCallbackUrl(),
            'scope' => 'offline_access accounting.transactions accounting.settings.read accounting.contacts.read',
        ];

        if (AngieApplication::isOnDemand()) {
            $data['state'] = AngieApplication::getAccountId();
        }

        $provider = new Xero($data);

        return $provider->getAuthorizationUrl($data);
    }

    /**
     * Authorize with Xero.
     *
     * @return $this
     * @throws Exception
     */
    public function authorize(array $params)
    {
        $provider = new Xero([
            'clientId' => $this->getClientId(),
            'clientSecret' => $this->getClientSecret(),
            'redirectUri' => $this->getCallbackUrl(),
        ]);

        $token = $provider->getAccessToken(AuthorizationCode::GRANT_TYPE, [
            'code' => array_var($params, 'code'),
        ]);

        $this->setAccessToken($token->getToken());
        $this->setTokenExpiresTimestamp($token->getExpires());
        $this->setRefreshToken($token->getRefreshToken());

        $tenants = $provider->getTenants($token);
        $this->setOrganizationName($tenants[0]->tenantName);
        $this->setXeroTenantId($tenants[0]->tenantId);

        $this->save();

        ConfigOptions::setValue('default_accounting_app', 'xero');

        return $this;
    }

    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        if (self::isSelfHosted()) {
            $result['client_id'] = $this->getClientId();
            $result['client_secret'] = $this->getClientSecret();
        }
        $result['has_valid_access'] = $this->hasValidAccess();
        $result['access_token'] = $this->getAccessToken();
        $result['xero_tenant_id'] = $this->getXeroTenantId();
        $result['default_xero_account'] = $this->getDefaultXeroAccount();
        $result['show_taxes_as'] = $this->getShowTaxesAs();
        $result['xero_organization_name'] = $this->getOrganizationName();
        $result['default_item_code'] = $this->getDefaultItemCode();

        return $result;
    }

    /**
     * Return new entity instance.
     *
     * @return \XeroPHP\Models\Accounting\Invoice
     * @throws Exception
     */
    public function createInvoice(array $attributes = [])
    {
        $this->checkAccessToken();

        $xero = new Application($this->getAccessToken(), $this->getXeroTenantId());
        $invoice = new XeroPHP\Models\Accounting\Invoice($xero);
        $invoice->setType(\XeroPHP\Models\Accounting\Invoice::INVOICE_TYPE_ACCREC);
        $invoice->setLineAmountType($this->getShowTaxesAs());
        $invoicing_default_due = ConfigOptions::getValue('invoicing_default_due', 0);
        $timestamp = DateTimeValue::now()->addDays($invoicing_default_due)->getTimestamp();
        $invoice->setDueDate(new \DateTime('@' . $timestamp));

        $client_id = isset($attributes['client_id']) ? $attributes['client_id'] : '';
        $is_client_load_by_name = isset($attributes['is_client_load_by_name']) ? $attributes['is_client_load_by_name'] : 0;

        if ($client_id > '') {
            $contact = new XeroPHP\Models\Accounting\Contact($xero);
            if ($is_client_load_by_name == 1) {
                $contact->setName($client_id);
            } else {
                $contact->setContactID($client_id);
            }
            $invoice->setContact($contact);
        }

        $items = isset($attributes['items']) ? $attributes['items'] : [];
        foreach ($items as $key => $item_attributes) {
            $unit_cost = isset($item_attributes['unit_cost']) ? $item_attributes['unit_cost'] : null;
            $quantity = isset($item_attributes['quantity']) ? $item_attributes['quantity'] : null;
            $description = isset($item_attributes['description']) ? $item_attributes['description'] : '';

            if ($unit_cost === null || $quantity === null) {
                continue;
            }

            $line_item = new XeroPHP\Models\Accounting\Invoice\LineItem();
            $line_item->setLineAmount($unit_cost * $quantity);
            $line_item->setDescription($description);
            $line_item->setQuantity($quantity);
            $line_item->setUnitAmount($unit_cost);
            $line_item->setAccountCode($this->getDefaultXeroAccount());

            $default_item_code = $this->getDefaultItemCode();
            if ($default_item_code && $this->checkDefaultItemCode($default_item_code)) {
                $line_item->setItemCode($this->getDefaultItemCode());
            }

            $invoice->addLineItem($line_item);
        }

        if (!count($invoice->getLineItems())) {
            throw new \Exception('No items attached to invoice');
        }

        try {
            $xero->save($invoice, true);
        } catch (UnauthorizedException $e) {
            $this->delete();
            throw $e;
        }

        return $invoice;
    }

    private function checkDefaultItemCode(string $item_code): bool
    {
        $response = $this->loadData(Item::class, 0, 'Code=="' . $item_code . '"');
        if ($response && $response->count()) {
            return true;
        }

        return false;
    }

    public function fetch(string $entity_name, array $ids = [], string $where = '', int $sync_timestamp = 0): array
    {
        $result = [];
        $namespace = explode('\\', $entity_name);
        $id_column_name = 'get' . $namespace[3] . 'ID';

        $page = 1;

        do {
            $xero_collection = $this->loadData($entity_name, $page, $where, $sync_timestamp);

            foreach ($xero_collection as $xero_entity) {
                if (!empty($ids)) {
                    if (!in_array($xero_entity->{$id_column_name}(), $ids)) {
                        continue;
                    }
                }
                $result[] = $xero_entity;
            }
            ++$page;
        } while (count($xero_collection) == 100);

        return $result;
    }

    /**
     * @return array
     */
    public function getCompanies()
    {
        $data = AngieApplication::memories()->get('xero_companies', ['timestamp' => 0, 'companies' => []]);

        if ($this->hasValidAccess() && !$this->isTokenExpired()) {
            $new_compaies = $this->loadData(Contact::class, 0, null, $data['timestamp']);

            foreach ($new_compaies as $new_company) {
                $data['companies'][$new_company->getContactID()] = $new_company->getName();
            }

            AngieApplication::memories()->set('xero_companies', [
                'timestamp' => DateTimeValue::now()->getTimestamp(),
                'companies' => $data['companies'],
            ]);
        }

        return $data['companies'];
    }

    /**
     * Return accounts.
     *
     * @return array
     */
    public function getAccounts()
    {
        $data = AngieApplication::memories()->get('xero_accounts', ['timestamp' => 0, 'accounts' => []]);

        if ($this->hasValidAccess() && !$this->isTokenExpired()) {
            $new_data = $this->loadData(Account::class, 0, null, $data['timestamp']);

            /** @var Account $account_entity */
            foreach ($new_data as $account_entity) {
                if ($account_entity->getClass() != Account::ACCOUNT_CLASS_TYPE_REVENUE) {
                    continue;
                }

                $data['accounts'][$account_entity->getAccountID()] = [
                    'code' => $account_entity->getCode(),
                    'name' => $account_entity->getName(),
                ];
            }

            AngieApplication::memories()->set('xero_accounts', [
                'timestamp' => DateTimeValue::now()->getTimestamp(),
                'accounts' => $data['accounts'],
            ]);
        }

        return array_values($data['accounts']);
    }

    public function getItemCodes(): array
    {
        $data = AngieApplication::memories()->get('xero_item_codes', ['timestamp' => 0, 'item_codes' => []]);

        if ($this->isInUse()) {
            $new_data = $this->loadData(Item::class, 0, null, 0);

            if ($new_data) {
                $new_item_codes = [];
                /** @var Item $item */
                foreach ($new_data as $item) {
                    $new_item_codes[$item->getName()] = [
                        'code' => $item->getCode(),
                        'name' => $item->getName(),
                    ];
                }

                AngieApplication::memories()->set('xero_item_codes', [
                    'timestamp' => DateTimeValue::now()->getTimestamp(),
                    'item_codes' => $new_item_codes,
                ]);

                return array_values($new_item_codes);
            }
        }

        return array_values($data['item_codes']);
    }

    public function loadById(string $entity_name, string $id)
    {
        $this->checkAccessToken();
        $xero = new Application($this->getAccessToken(), $this->getXeroTenantId());

        return $xero->loadByGUID($entity_name, $id);
    }

    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Begin: delete integration @' . __CLASS__);

            if ($this->getClientId() && $this->getClientSecret() && $this->getRefreshToken()) {
                $this->revokeTokens();
            }

            parent::delete($bulk);

            AngieApplication::memories()->forget('xero_companies');
            AngieApplication::memories()->forget('xero_accounts');
            AngieApplication::memories()->forget('xero_item_codes');

            ConfigOptions::setValue('default_accounting_app', null);

            DB::commit('Done: delete integration @' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: delete integration @' . __CLASS__);
            throw $e;
        }
    }

    private function revokeTokens()
    {
        $ch = curl_init(self::REVOCATION_ENDPOINT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode($this->getClientId() . ':' . $this->getClientSecret())]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['token' => $this->getRefreshToken()]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_exec($ch);
        curl_close($ch);
        unset($ch);
    }

    public function canView(User $user): bool
    {
        return $user->isOwner();
    }

    public function canEdit(User $user): bool
    {
        return $user->isOwner();
    }

    public function canDelete(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * @return bool
     */
    private static function isSelfHosted()
    {
        return !AngieApplication::isOnDemand();
    }

    /**
     * Request data from xero.
     *
     * @param  string                    $entity_name
     * @param  int                       $page
     * @param  string                    $where
     * @param  int                       $modified_after
     * @return XeroPHP\Remote\Collection
     */
    public function loadData($entity_name, $page = 0, $where = null, $modified_after = null)
    {
        $this->checkAccessToken();
        $xero = new Application($this->getAccessToken(), $this->getXeroTenantId());
        try {
            $query = $xero->load($entity_name);

            if ($where) {
                $query->where($where);
            }

            if ($page) {
                $query->page($page);
            }

            if ($modified_after) {
                $query->modifiedAfter(new DateTime('@' . $modified_after));
            }

            return $query->execute();
        } catch (ForbiddenException | UnauthorizedException $e) {
            $this->delete();
            throw $e;
        }
    }

    /**
     * Return status of access token.
     *
     * @throw UnauthorizedException
     */
    public function checkAccessToken()
    {
        try {
            if ($this->isTokenExpired()) {
                $provider = new Xero([
                    'clientId' => $this->getClientId(),
                    'clientSecret' => $this->getClientSecret(),
                    'redirectUri' => $this->getCallbackUrl(),
                ]);
                $newAccessToken = $provider->getAccessToken('refresh_token', [
                    'refresh_token' => $this->getRefreshToken(),
                ]);
                $this->setAccessToken($newAccessToken->getToken());
                $this->setRefreshToken($newAccessToken->getRefreshToken());
                $this->setTokenExpiresTimestamp($newAccessToken->getExpires());
                $this->save();
            }
        } catch (Exception $e) {
            $this->delete();
            throw ($e);
        }
    }
}
