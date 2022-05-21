<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait ILabelImplementation
{
    public function registerILabelImplementation(): void
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $result['label_id'] = $this->getLabelId();
            }
        );

        $this->registerEventHandler(
            'on_history_field_renderers',
            function (array & $renderers) {
                $renderers['label_id'] = function (
                    $old_value,
                    $new_value,
                    Language $language
                ) {
                    if ($new_value) {
                        if ($old_value) {
                            return lang(
                                'Label changed from <b>:old_value</b> to <b>:new_value</b>',
                                [
                                    'old_value' => Labels::getLabelName($old_value, lang('Unknown Label')),
                                    'new_value' => Labels::getLabelName($new_value, lang('Unknown Label')),
                                ],
                                true,
                                $language
                            );
                        } else {
                            return lang(
                                'Label set to <b>:new_value</b>',
                                [
                                    'new_value' => Labels::getLabelName($new_value, lang('Unknown Label')),
                                ],
                                true,
                                $language
                            );
                        }
                    } else {
                        if ($old_value) {
                            return lang(
                                'Label <b>:old_value</b> removed',
                                [
                                    'old_value' => Labels::getLabelName($old_value, lang('Unknown Label')),
                                ],
                                true,
                                $language
                            );
                        }
                    }
                };
            }
        );
    }

    /**
     * Return label for the given object.
     *
     * @return Label|null
     */
    public function getLabel()
    {
        return DataObjectPool::get(Label::class, $this->getLabelId());
    }

    /**
     * Set label.
     *
     * @param Label|null $label
     * @param bool       $save
     */
    public function setLabel($label, $save = false)
    {
        if ($label instanceof Label) {
            $this->setLabelId($label->getId());
        } elseif ($label === null) {
            $this->setLabelId(0);
        } else {
            throw new InvalidInstanceError('label', $label, 'Label');
        }

        if ($save) {
            $this->save();
        }
    }

    // ---------------------------------------------------
    //  Expectation
    // ---------------------------------------------------

    /**
     * Return value of label_id field.
     *
     * @return int
     */
    abstract public function getLabelId();

    /**
     * Set value of label_id field.
     *
     * @param  int $value
     * @return int
     */
    abstract public function setLabelId($value);

    abstract protected function registerEventHandler(string $event, callable $handler): void;
}
