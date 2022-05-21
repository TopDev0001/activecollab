<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * ActiveCollab installer adapter.
 *
 * @package ActiveCollab.resources
 */
class ActiveCollabInstallerAdapter extends AngieApplicationInstallerAdapter
{
    /**
     * Construct installer adapter.
     */
    public function __construct()
    {
        $this->setMinMemory(64);

        $this->addWritableFolder(['activecollab', 'public/assets']);
    }

    /**
     * Return installer sections.
     *
     * @return array
     */
    public function getSections()
    {
        return array_merge(parent::getSections(), ['finish' => 'Finish']);
    }

    /**
     * Render initial section content.
     *
     * @param  string $name
     * @return string
     */
    public function getSectionContent($name)
    {
        if ($name === 'finish') {
            return '<p>Done, you have successfully installed activeCollab!</p>' .
                '<p><button type="button" id="application_installer_done">Log in Now!</button></p>' .
                '<script type="text/javascript">
                    $("#application_installer_done").click(function () {
                      var installer_url = window.location.href.split(\'/\');
                      if (installer_url[installer_url.length - 1].indexOf(\'index.php\') === 0) installer_url.splice(installer_url.length - 1);
                      window.location = installer_url.join(\'/\');
                      $(this).prop("disabled", true)
                    });
                </script>';
        }

        return parent::getSectionContent($name);
    }

    /**
     * Return a list of modules that need to be installed.
     *
     * @return array
     */
    public function getModulesToInstall()
    {
        $modules = parent::getModulesToInstall();

        // Make sure that invoicing module is installed after tracking module
        $invoicing = array_search('invoicing', $modules);
        $tracking = array_search('tracking', $modules);

        if ($invoicing !== false && $tracking !== false && $invoicing < $tracking) {
            unset($modules[$invoicing]);
            $modules[] = 'invoicing';
        }

        return $modules;
    }

    /**
     * Return prepared admin params.
     *
     * @param  array $from
     * @return array
     */
    public function getOwnerParams($from)
    {
        $params = parent::getOwnerParams($from);

        if (empty($params['company_name'])) {
            $params['company_name'] = '';
        }

        return $params;
    }

    /**
     * Create owner user account and return owners's user ID.
     *
     * @param  string $email
     * @param  string $password
     * @return int
     */
    public function createOwner($email, $password, array $other_params = null)
    {
        $admin_id = parent::createOwner($email, $password, $other_params);

        if (isset($other_params['company_name']) && trim($other_params['company_name'])) {
            DB::execute('UPDATE companies SET name = ? WHERE id = ?', trim($other_params['company_name']), 1);
        }

        return $admin_id;
    }
}
