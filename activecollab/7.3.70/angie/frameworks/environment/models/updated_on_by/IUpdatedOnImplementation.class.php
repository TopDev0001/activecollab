<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

trait IUpdatedOnImplementation
{
    public function registerIUpdatedOnImplementation(): void
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $result['updated_on'] = $this->getUpdatedOn();
            }
        );

        $this->registerEventHandler(
            'on_before_save',
            function ($is_new, $modifications) {
                if (empty($modifications['updated_on'])) {
                    $this->setUpdatedOn(new DateTimeValue());
                }
            }
        );
    }

    // ---------------------------------------------------
    //  Expectatons
    // ---------------------------------------------------

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Return value of updated_on field.
     *
     * @return DateTimeValue
     */
    abstract public function getUpdatedOn();

    /**
     * Set value of updated_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    abstract public function setUpdatedOn($value);
}
