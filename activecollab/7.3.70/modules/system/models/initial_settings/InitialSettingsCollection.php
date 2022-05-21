<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;

/**
 * Application level initial settings collection.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class InitialSettingsCollection extends FwInitialSettingsCollection
{
    public function execute()
    {
        return array_merge(
            parent::execute(),
            [
                'project_labels' => Labels::getLabelsDetailsByType(ProjectLabel::class),
                'task_labels' => Labels::getLabelsDetailsByType(TaskLabel::class),
                'available_reactions' => Reactions::getAvailableTypes(),
                'label_colors' => Labels::getColorPalette(),
            ]
        );
    }

    protected function onLoadSettings(array &$settings, User $user)
    {
        if ($user instanceof User) {
            $settings['invoicing_default_due'] = ConfigOptions::getValue('invoicing_default_due');
            $settings['default_project_label_id'] = Labels::findDefaultId(ProjectLabel::class);
            $settings['on_invoice_based_on'] = ConfigOptions::getValue('on_invoice_based_on');
            $settings['owner_company_id'] = AngieApplication::getContainer()
                ->get(OwnerCompanyResolverInterface::class)
                    ->getId();
            $settings['default_task_list_name'] = ConfigOptions::getValue('default_task_list_name');
        } else {
            throw new InvalidInstanceError('user', $user, 'User');
        }
    }

    protected function onLoadCollections(array &$collections, User $user)
    {
        if ($user instanceof User) {
            $collections['project_categories'] = Categories::prepareCollection(
                'project_categories',
                $user
            );
        } else {
            throw new InvalidInstanceError('user', $user, 'User');
        }
    }
}
