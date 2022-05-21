<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Foundation\History\Renderers;

use Language;
use LogicException;

class LabelsHistoryFieldRenderer implements HistoryFieldRendererInterface
{
    private $id_to_names_resolver;

    public function __construct(callable $id_to_names_resolver)
    {
        $this->id_to_names_resolver = $id_to_names_resolver;
    }

    public function render(
        $old_value,
        $new_value,
        Language $language
    ): ?string
    {
        if (empty($old_value) || !is_array($old_value)) {
            $old_value = [];
        }

        if (empty($new_value) || !is_array($new_value)) {
            $new_value = [];
        }

        $label_ids = [];

        if (!empty($old_value)) {
            $label_ids = array_merge($label_ids, $old_value);
        }

        if (!empty($new_value)) {
            $label_ids = array_merge($label_ids, $new_value);
        }

        if (empty($label_ids)) {
            return '';
        }

        $label_ids = array_unique($label_ids);

        $label_names = call_user_func($this->id_to_names_resolver, $label_ids);

        if (!is_array($label_names)) {
            throw new LogicException('Label names resolver did not return an expected result.');
        }

//        $label_ids = array_intersect($label_ids, array_keys($label_names));

        if ($this->labelsAreAdded($old_value, $new_value)) {
            $added_labels = array_diff($new_value, $old_value);

            if (count($added_labels) === 1) {
                return lang(
                    'Label :list_of_labels added',
                    [
                        'list_of_labels' => $this->getNamesListForIds(
                            $added_labels,
                            $label_names,
                            $language
                        ),
                    ],
                    false,
                    $language
                );
            } else {
                return lang(
                    'Labels :list_of_labels added',
                    [
                        'list_of_labels' => $this->getNamesListForIds(
                            $added_labels,
                            $label_names,
                            $language
                        ),
                    ],
                    false,
                    $language
                );
            }
        } elseif ($this->labelsAreRemoved($old_value, $new_value)) {
            $labels_removed = array_diff($old_value, $new_value);

            if (count($labels_removed) === 1) {
                return lang(
                    'Label :list_of_labels removed',
                    [
                        'list_of_labels' => $this->getNamesListForIds(
                            $labels_removed,
                            $label_names,
                            $language
                        ),
                    ],
                    false,
                    $language
                );
            } else {
                return lang(
                    'Labels :list_of_labels removed',
                    [
                        'list_of_labels' => $this->getNamesListForIds(
                            $labels_removed,
                            $label_names,
                            $language
                        ),
                    ],
                    false,
                    $language
                );
            }
        } elseif ($this->labelsAreUpdated($old_value, $new_value)) {
            return lang(
                'Labels added: :labels_added; Labels removed: :labels_removed',
                [
                    'labels_added' => $this->getNamesListForIds(
                        array_diff($new_value, $old_value),
                        $label_names,
                        $language
                    ),
                    'labels_removed' => $this->getNamesListForIds(
                        array_diff($old_value, $new_value),
                        $label_names,
                        $language
                    ),
                ],
                false,
                $language
            );
        }

        return '';
    }

    private function getNamesListForIds(array $label_ids, array $label_names, Language $language): string
    {
        $marked_up_names = [];

        foreach ($label_ids as $label_id) {
            if (empty($label_names[$label_id])) {
                $marked_up_names[] = sprintf('<i>DELETED LABEL</i>');
            } else {
                $marked_up_names[] = sprintf('<b>%s</b>', clean($label_names[$label_id]));
            }
        }

        sort($marked_up_names);

        if (count($marked_up_names) > 1) {
            return lang(
                ':list_of_labels and :last_label',
                [
                    'list_of_labels' => implode(
                        ', ', array_slice($marked_up_names, 0, count($marked_up_names) - 1)
                    ),
                    'last_label' => $marked_up_names[count($marked_up_names) - 1],
                ],
                false,
                $language
            );
        } else {
            return $marked_up_names[0];
        }
    }

    private function labelsAreAdded(array $old_label_ids, array $new_label_ids): bool
    {
        if (empty($old_label_ids)) {
            return true;
        }

        $diff = array_diff($new_label_ids, $old_label_ids);

        return !empty($diff) && count($diff) === (count($new_label_ids) - count($old_label_ids));
    }

    private function labelsAreRemoved(array $old_label_ids, array $new_label_ids): bool
    {
        if (count($old_label_ids) > count($new_label_ids)) {
            $diff = array_diff($old_label_ids, $new_label_ids);

            return !empty($diff) && count($diff) === (count($old_label_ids) - count($new_label_ids));
        }

        return false;
    }

    private function labelsAreUpdated(array $old_label_ids, array $new_label_ids): bool
    {
        return count($old_label_ids) != count($new_label_ids) ||
            !empty(array_diff($old_label_ids, $new_label_ids));
    }
}
