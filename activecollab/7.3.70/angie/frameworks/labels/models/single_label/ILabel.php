<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Labels interface definition.
 *
 * @package angie.frameworks.labels
 */
interface ILabel
{
    /**
     * Return label for the given object.
     *
     * @return Label|null
     */
    public function getLabel();

    /**
     * Set label.
     *
     * @param  Label|null           $label
     * @param  bool                 $save
     * @throws InvalidInstanceError
     */
    public function setLabel($label, $save = false);

    /**
     * Return value of label_id field.
     *
     * @return int
     */
    public function getLabelId();

    /**
     * Set value of label_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setLabelId($value);
}
