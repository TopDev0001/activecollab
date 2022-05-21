<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tracking\Utils\BudgetNotificationsManagerInterface;

trait IBudgetThresholdsImplementation
{
    /**
     * @var array List of thresholds
     */
    private array $thresholds = [];

    /**
     * @var bool Only need to save if budget_thresholds attr was sent
     */
    private bool $shouldEditThresholds = false;

    public function registerIBudgetThresholdsImplementation(): void
    {
        $this->registerEventHandler('on_set_attribute', function ($attribute, $value) {
            if ($attribute == 'budget_thresholds' && is_array($value)) {
                // reset if not empty
                if (count($this->thresholds)) {
                    $this->thresholds = [];
                }
                foreach ($value as $threshold) {
                    if (is_numeric($threshold) && !in_array($threshold, $this->thresholds)) {
                        $this->thresholds[] = $threshold;
                    }
                }
                $this->shouldEditThresholds = true;
            }
        });

        $this->registerEventHandler('on_after_save', function () {
            if ($this->shouldEditThresholds && is_array($this->thresholds)) {
                AngieApplication::getContainer()->get(BudgetNotificationsManagerInterface::class)->batchEditThresholds($this->thresholds, $this->getId());
            }
        });
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Return parent object ID.
     *
     * @return int
     */
    abstract public function getId();
}
