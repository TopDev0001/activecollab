<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait ICreatedOnImplementation
{
    public function registerICreatedOnImplementation()
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $result['created_on'] = $this->getCreatedOn();
            }
        );

        $this->registerEventHandler(
            'on_before_save',
            function ($is_new, $modifications) {
                if ($is_new && empty($modifications['created_on'])) {
                    $this->setCreatedOn(new DateTimeValue());
                }
            }
        );
    }

    // ---------------------------------------------------
    //  Expectatons
    // ---------------------------------------------------

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Return value of created_on field.
     *
     * @return DateTimeValue
     */
    abstract public function getCreatedOn();

    /**
     * Set value of created_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    abstract public function setCreatedOn($value);
}
