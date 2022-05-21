<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class Label extends BaseLabel implements LabelInterface
{
    const COLOR_PALETTE = [
        '#FDF196' => ['darker_text' => '#948D5D', 'lighter_text' => '#EBD429'],
        '#FBBB75' => ['darker_text' => '#916C43', 'lighter_text' => '#E99841'],
        '#EAC2AD' => ['darker_text' => '#877064', 'lighter_text' => '#B98A72'],
        '#FBD6E7' => ['darker_text' => '#917C86', 'lighter_text' => '#E4A1C0'],
        '#FF9C9C' => ['darker_text' => '#945A5A', 'lighter_text' => '#DB4545'],
        '#C49CB6' => ['darker_text' => '#715A69', 'lighter_text' => '#B16E9A'],
        '#BEACF9' => ['darker_text' => '#6E6390', 'lighter_text' => '#9B86DF'],
        '#BDF7FD' => ['darker_text' => '#6D8F92', 'lighter_text' => '#7BDDE7'],
        '#BEEAFF' => ['darker_text' => '#6E8794', 'lighter_text' => '#92BFD4'],
        '#A0CBFD' => ['darker_text' => '#5C7592', 'lighter_text' => '#568CCB'],
        '#B9E4E0' => ['darker_text' => '#6B8482', 'lighter_text' => '#70BFB8'],
        '#C3E799' => ['darker_text' => '#718658', 'lighter_text' => '#80C333'],
        '#98B57C' => ['darker_text' => '#647157', 'lighter_text' => '#819F65'],
        '#DDDDDD' => ['darker_text' => '#808080', 'lighter_text' => '#ACACAC'],
        '#CACACA' => ['darker_text' => '#757575', 'lighter_text' => '#7D7D7D'],
    ];

    const LABEL_DEFAULT_COLOR = '#DDDDDD';
    const LABEL_DEFAULT_COLOR_SHORT = '#DDD';

    protected ?array $accept = [
        'name',
        'is_default',
    ];

    protected bool $always_uppercase = true;

    public function getBaseTypeName(bool $singular = true): string
    {
        return $singular ? 'label' : 'labels';
    }

    public function getColor()
    {
        return parent::getColor() ? strtoupper(parent::getColor()) : self::LABEL_DEFAULT_COLOR;
    }

    /**
     * Darker text color.
     *
     * @return string
     */
    public function getDarkerTextColor()
    {
        return self::COLOR_PALETTE[$this->getColor()]['darker_text'] ?? self::LABEL_DEFAULT_COLOR;
    }

    /**
     * Lighter text color.
     *
     * @return string
     */
    public function getLighterTextColor()
    {
        return self::COLOR_PALETTE[$this->getColor()]['lighter_text'] ?? self::LABEL_DEFAULT_COLOR;
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'color' => $this->getColor(),
                'lighter_text_color' => $this->getLighterTextColor(),
                'darker_text_color' => $this->getDarkerTextColor(),
                'is_default' => $this->getIsDefault(),
                'position' => $this->getPosition(),
            ]
        );
    }

    public function getRoutingContext(): string
    {
        return 'label';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'label_id' => $this->getId(),
        ];
    }

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
     * Return max label name.
     *
     * @return int
     */
    public function getMaxNameLength()
    {
        return 50;
    }

    /**
     * Set value of specific field.
     *
     * @param  string            $name
     * @param  mixed             $value
     * @return mixed
     * @throws InvalidParamError
     */
    public function setFieldValue($name, $value)
    {
        if ($name == 'name') {
            if (strlen_utf($value) > $this->getMaxNameLength()) {
                $value = substr_utf($value, 0, $this->getMaxNameLength());
            }

            if ($this->getAlwaysUppercase()) {
                $value = mb_strtoupper($value);
            }
        }

        return parent::setFieldValue($name, $value);
    }

    /**
     * Return true if name of this label needs to be displayed in uppercase.
     *
     * @return bool
     */
    public function getAlwaysUppercase()
    {
        return (bool) $this->always_uppercase;
    }

    public function canView(User $user): bool
    {
        return true;
    }

    public function canEdit(User $user): bool
    {
        return $user->isOwner();
    }

    public function canDelete(User $user): bool
    {
        return !$this->getIsDefault() && $user->isOwner();
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        if ($this->validatePresenceOf('name')) {
            $this->validateUniquenessOf('type', 'name') or $errors->fieldValueNeedsToBeUnique('name');
        } else {
            $errors->fieldValueIsRequired('name');
        }
    }

    /**
     * Save label.
     */
    public function save()
    {
        $drop_cache = $this->isNew() || $this->isModifiedField('name') || $this->isModifiedField('color');

        if (!$this->getPosition()) {
            $this->setPosition(DB::executeFirstCell('SELECT MAX(position) FROM labels WHERE type = ?', get_class($this)) + 1);
        }

        $save = parent::save();

        if ($drop_cache) {
            Labels::clearCache();
        }

        return $save;
    }

    /**
     * Delete specific object (and related objects if neccecery).
     *
     * @param  bool      $bulk
     * @throws Exception
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Begin: delete label @ ' . __CLASS__);

            DB::execute('DELETE FROM parents_labels WHERE label_id = ?', $this->getId());
            parent::delete($bulk);

            DB::commit('Done: delete label @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: delete label @ ' . __CLASS__);
            throw $e;
        }
    }
}
