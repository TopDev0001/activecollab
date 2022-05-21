<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;

class Language extends BaseLanguage implements RoutingContextInterface
{
    /**
     * Return locale code without UTF-8 suffix.
     *
     * @return string
     */
    public function getLocaleCode()
    {
        return Languages::getLocaleCode($this->getLocale());
    }

    /**
     * Get language translation.
     *
     * @return array
     */
    public function getDictionaryTranslations()
    {
        $translation_file_path = APPLICATION_PATH . '/localization/' . $this->getLocale() . '-backend.php';

        if (is_file($translation_file_path)) {
            $result = require $translation_file_path;

            if (is_array($result)) {
                return $result;
            }
        }

        return [];
    }

    /**
     * Set value of specific field.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return mixed
     */
    public function setFieldValue($name, $value)
    {
        if ($name == 'locale' && $value && !str_ends_with(strtolower($value), 'utf-8')) {
            $value = "{$value}.UTF-8"; // Make sure that we include charset in locale
        }

        return parent::setFieldValue($name, $value);
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['locale'] = $this->getLocale();
        $result['is_default'] = $this->getIsDefault();
        $result['is_rtl'] = $this->getIsRtl();
        $result['is_community_translation'] = $this->getIsCommunityTranslation();
        $result['decimal_separator'] = $this->getDecimalSeparator();
        $result['thousand_separator'] = $this->getThousandsSeparator();

        return $result;
    }

    public function getRoutingContext(): string
    {
        return 'language';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'language_id' => $this->getId(),
        ];
    }

    public function canView(User $user): bool
    {
        if ($this->isBuiltIn()) {
            return false;
        }

        return $user->isOwner();
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Returns true if this locale is built in the code.
     *
     * @return bool
     */
    public function isBuiltIn()
    {
        return $this->getLocale() == BUILT_IN_LOCALE;
    }

    public function canEdit(User $user): bool
    {
        if ($this->isBuiltIn()) {
            return false;
        }

        return $user->isOwner();
    }

    public function canDelete(User $user): bool
    {
        if ($this->isBuiltIn() || $this->getIsDefault()) {
            return false;
        }

        return $user->isOwner();
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
            $this->validateUniquenessOf('name') or $errors->fieldValueNeedsToBeUnique('name');
        } else {
            $errors->fieldValueIsRequired('name');
        }

        if ($this->validatePresenceOf('locale')) {
            if (strtolower($this->getLocale()) == 'en_us.utf-8') {
                $errors->addError('en_US.UTF-8 locale is reserved by the system', 'locale');
            }

            $this->validateUniquenessOf('locale') or $errors->fieldValueNeedsToBeUnique('locale');
        } else {
            $errors->fieldValueIsRequired('locale');
        }
    }
}
