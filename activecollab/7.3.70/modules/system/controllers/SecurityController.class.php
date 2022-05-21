<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Firewall\IpAddress;
use ActiveCollab\Module\System\SystemModule;
use Angie\Authentication\Firewall\Firewall;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('admin', SystemModule::NAME);

class SecurityController extends AdminController
{
    /**
     * @return array
     */
    public function show_settings()
    {
        return $this->getOptionsResult();
    }

    /**
     * @return array|int
     */
    public function save_settings(Request $request, User $user)
    {
        try {
            [
                $firewall_enabled,
                $white_list,
                $black_list,
                $brute_force_protection_enabled,
                $password_policy_min_length,
                $password_policy_require_numbers,
                $password_policy_require_mixed_case,
                $password_policy_require_symbols
            ] = $this->getOptionsFromRequest($request);

            $firewall = new Firewall($firewall_enabled, $white_list, $black_list);
        } catch (Exception $e) {
            AngieApplication::log()->notice('Invalid security settings request.', [
                'exception' => $e,
            ]);

            return Response::BAD_REQUEST;
        }

        $ip_address = $request->getAttribute('visitor_ip_address');

        if (!$ip_address) {
            AngieApplication::log()->notice('IP address not resolved.');

            return Response::BAD_REQUEST;
        }

        if ($firewall->shouldBlock(new IpAddress($ip_address))) {
            return Response::CONFLICT;
        }

        ConfigOptions::setValue([
            'firewall_enabled' => $firewall_enabled,
            'firewall_white_list' => $white_list,
            'firewall_black_list' => $black_list,
            'brute_force_protection_enabled' => $brute_force_protection_enabled,
            'password_policy_min_length' => $password_policy_min_length,
            'password_policy_require_numbers' => $password_policy_require_numbers,
            'password_policy_require_mixed_case' => $password_policy_require_mixed_case,
            'password_policy_require_symbols' => $password_policy_require_symbols,
        ]);

        return $this->getOptionsResult();
    }

    /**
     * @return array
     */
    private function getOptionsResult()
    {
        $options = ConfigOptions::getValue(array_keys($this->getOptionsUnderManagement()));

        $result = [];

        foreach ($this->getOptionsUnderManagement() as $option_name => $option_type) {
            switch ($option_type) {
                case self::BOOL_OPTION:
                    $result[$option_name] = (bool) $options[$option_name];
                    break;
                case self::INT_OPTION:
                    $result[$option_name] = (int) $options[$option_name];
                    break;
                case self::ARRAY_OPTION:
                    $result[$option_name] = is_array($options[$option_name]) ? $options[$option_name] : [];
                    break;
            }
        }

        return $result;
    }

    /**
     * Fetch firewall options from $request.
     *
     * @return array
     */
    private function getOptionsFromRequest(Request $request)
    {
        $parsed_body = $request->getParsedBody();

        foreach (array_keys($this->getOptionsUnderManagement()) as $required_option) {
            if (!array_key_exists($required_option, $parsed_body)) {
                throw new RuntimeException("Option '$required_option' not found.");
            }
        }

        return [
            (bool) $parsed_body['firewall_enabled'],
            $this->prepareFirewallList($parsed_body['firewall_white_list']),
            $this->prepareFirewallList($parsed_body['firewall_black_list']),
            (bool) $parsed_body['brute_force_protection_enabled'],
            (int) $parsed_body['password_policy_min_length'],
            (bool) $parsed_body['password_policy_require_numbers'],
            (bool) $parsed_body['password_policy_require_mixed_case'],
            (bool) $parsed_body['password_policy_require_symbols'],
        ];
    }

    /**
     * Process firewall string list, and return it as an array.
     *
     * @param  string $list
     * @return array
     */
    private function prepareFirewallList($list)
    {
        $result = [];

        if (is_string($list)) {
            foreach (explode("\n", $list) as $rule) {
                if (!empty(trim($rule))) {
                    $result[] = trim($rule);
                }
            }
        } elseif (is_array($list)) {
            $result = $list;
        }

        return $result;
    }

    const BOOL_OPTION = 'bool';
    const INT_OPTION = 'int';
    const ARRAY_OPTION = 'array';

    /**
     * Return a list of options that are under management.
     *
     * @return array
     */
    private function getOptionsUnderManagement()
    {
        return [
            'firewall_enabled' => self::BOOL_OPTION,
            'firewall_white_list' => self::ARRAY_OPTION,
            'firewall_black_list' => self::ARRAY_OPTION,
            'brute_force_protection_enabled' => self::BOOL_OPTION,
            'password_policy_min_length' => self::INT_OPTION,
            'password_policy_require_numbers' => self::BOOL_OPTION,
            'password_policy_require_mixed_case' => self::BOOL_OPTION,
            'password_policy_require_symbols' => self::BOOL_OPTION,
        ];
    }
}
