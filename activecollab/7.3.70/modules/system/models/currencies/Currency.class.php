<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Globalization;

class Currency extends BaseCurrency
{
    /**
     * Return properly formatted value.
     *
     * @param  float    $value
     * @param  Language $language
     * @param  bool     $with_currency_code
     * @return string
     */
    public function format($value, $language = null, $with_currency_code = false)
    {
        return Globalization::formatMoney($value, $this, $language, $with_currency_code);
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['code'] = $this->getCode();
        $result['is_default'] = $this->getIsDefault();
        $result['decimal_spaces'] = $this->getDecimalSpaces();
        $result['decimal_rounding'] = $this->getDecimalRounding();

        return $result;
    }

    public function getRoutingContext(): string
    {
        return 'currency';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'currency_id' => $this->getId(),
        ];
    }

    public function canView(User $user): bool
    {
        return $user->isOwner();
    }

    public function canEdit(User $user): bool
    {
        return $user->isOwner();
    }

    public function canDelete(User $user): bool
    {
        return $user->isOwner() && !$this->getIsDefault()
            ? empty(Invoices::countByCurrency($this)) && empty(Projects::countByCurrency($this))
            : false;
    }

    public function validate(ValidationErrors & $errors)
    {
        if ($this->validatePresenceOf('name')) {
            if (!$this->validateUniquenessOf('name')) {
                $errors->fieldValueNeedsToBeUnique('name');
            }
        } else {
            $errors->fieldValueIsRequired('name');
        }

        if ($this->validatePresenceOf('code')) {
            if (!$this->validateUniquenessOf('code')) {
                $errors->fieldValueNeedsToBeUnique('code');
            }
        } else {
            $errors->fieldValueIsRequired('code');
        }
    }

    public function save()
    {
        $save = parent::save();

        AngieApplication::cache()->remove('currencies_id_name_map');
        AngieApplication::cache()->remove('currencies_id_details_map');

        return $save;
    }
}
