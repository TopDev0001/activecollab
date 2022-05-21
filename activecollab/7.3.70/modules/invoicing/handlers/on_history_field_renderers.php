<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Handle on_history_field_renderers event.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage handlers
 */

/**
 * Get history changes as log text.
 *
 * @param ApplicationObject $object
 * @param array             $renderers
 */
function invoicing_handle_on_history_field_renderers($object, &$renderers)
{
    if ($object instanceof IInvoice) {
        if ($object instanceof RecurringProfile) {
            $renderers['frequency'] = function ($old_value, $new_value, Language $language) {
                if ($new_value) {
                    if ($old_value) {
                        return lang('Frequency changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                    } else {
                        return lang('Frequency set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                    }
                } else {
                    if ($old_value) {
                        return lang('Frequency set to empty value', null, true, $language);
                    }
                }
            };
            $renderers['occurrences'] = function ($old_value, $new_value, Language $language) {
                if ($new_value) {
                    if ($old_value) {
                        return lang('Occurrences changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                    } else {
                        return lang('Occurrences set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                    }
                } else {
                    if ($old_value) {
                        return lang('Occurrences set to empty value', null, true, $language);
                    }
                }
            };

            $renderers['stored_card_id'] = function ($old_value, $new_value, Language $language) {
                if ($new_value) {
                    if ($old_value) {
                        return lang('Stored Card id changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                    } else {
                        return lang('Stored Card id set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                    }
                } else {
                    if ($old_value) {
                        return lang('Stored Card id set to empty value', null, true, $language);
                    }
                }
            };
        }//for recurringProfile

        $renderers['project_id'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Project id changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Project id changed set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Project id changed set to empty value', null, true, $language);
                }
            }
        };

        $renderers['currency_id'] = function ($old_value, $new_value, Language $language) {
            $new_currency = DataObjectPool::get('Currency', $new_value);
            $old_currency = DataObjectPool::get('Currency', $old_value);

            if ($new_currency instanceof Currency) {
                if ($old_currency instanceof Currency) {
                    return lang('Currency changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_currency->getCode(), 'new_value' => $new_currency->getCode()], true, $language);
                } else {
                    return lang('Currency set to <b>:new_value</b>', ['new_value' => $new_currency->getCode()], true, $language);
                }
            } else {
                if ($old_currency instanceof Currency || is_null($new_currency)) {
                    return lang('Currency set to empty value', null, true, $language);
                }
            }
        };
        $renderers['number'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Invoice Number changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Invoice Number set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Invoice Number set to empty value', null, true, $language);
                }
            }
        };
        $renderers['note'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Note updated', null, true, $language);
                } else {
                    return lang('Note added', null, true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Note removed', null, true, $language);
                }
            }
        };
        $renderers['private_note'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Private Note updated', null, true, $language);
                } else {
                    return lang('Private Note added', null, true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Private Note removed', null, true, $language);
                }
            }
        };
        $renderers['company_address'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Company Address changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Company Address set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Company Address set to empty value', null, true, $language);
                }
            }
        };
        $renderers['purchase_order_number'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Purchase Order Number changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Purchase Order Number set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Purchase Order Number set to empty value', null, true, $language);
                }
            }
        };
        $renderers['issued_on'] = function ($old_value, $new_value, Language $language) {
            if ($old_value && $new_value) {
                return lang('Issue date changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => DateValue::makeFromString($old_value)->formatForUser(null, 0, $language), 'new_value' => DateValue::makeFromString($new_value)->formatForUser(null, 0, $language)], true, $language);
            } else {
                if ($new_value) {
                    return lang('Issue date set to <b>:new_value</b> hours', ['new_value' => DateValue::makeFromString($new_value)->formatForUser(null, 0, $language)], true, $language);
                } else {
                    if ($old_value) {
                        return lang('Issue date removed', null, true, $language);
                    }
                }
            }
        };
        $renderers['due_on'] = function ($old_value, $new_value, Language $language) {
            if ($old_value && $new_value) {
                return lang('Due date changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => DateValue::makeFromString($old_value)->formatForUser(null, 0, $language), 'new_value' => DateValue::makeFromString($new_value)->formatForUser(null, 0, $language)], true, $language);
            } else {
                if ($new_value) {
                    return lang('Due date set to <b>:new_value</b> hours', ['new_value' => DateValue::makeFromString($new_value)->formatForUser(null, 0, $language)], true, $language);
                } else {
                    if ($old_value) {
                        return lang('Due date removed', null, true, $language);
                    }
                }
            }
        };
        $renderers['is_canceled'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                return lang('Canceled', null, true, $language);
            }
        };
        $renderers['company_id'] = function ($old_value, $new_value, Language $language) {
            $new_company = DataObjectPool::get('Company', $new_value);
            $old_company = DataObjectPool::get('Company', $old_value);

            if ($new_company instanceof Company) {
                if ($old_company instanceof Company) {
                    return lang('Company changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_company->getName(), 'new_value' => $new_company->getName()], true, $language);
                } else {
                    return lang('Company set to <b>:new_value</b>', ['new_value' => $new_company->getName()], true, $language);
                }
            } else {
                if ($old_company instanceof Company || is_null($new_company)) {
                    return lang('Company removed', null, true, $language);
                }
            }
        };
        $renderers['company_name'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Company Name changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Company Name set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Company Name set to empty value', null, true, $language);
                }
            }
        };
        $renderers['company_address'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Company Address changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Company Address set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Company Address set to empty value', null, true, $language);
                }
            }
        };
        $renderers['discount_rate'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Discount changed from <b>:old_value%</b> to <b>:new_value%</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Discount set to <b>:new_value%</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Discount rate set to empty value', null, true, $language);
                }
            }
        };
        $renderers['email_from_name'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Email from name changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Email from name set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Email from name set to empty value', null, true, $language);
                }
            }
        };
        $renderers['email_from_email'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Email address from changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Email address from set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Email address from set to empty value', null, true, $language);
                }
            }
        };
        $renderers['email_from_id'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Email from ID changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Email from ID set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Email form ID from set to empty value', null, true, $language);
                }
            }
        };
        $renderers['start_on'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Start On date changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Start On date set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Start On date set to empty value', null, true, $language);
                }
            }
        };
        $renderers['is_enabled'] = function ($old_value, $new_value, Language $language) {
            $new_title = $new_value == 1 ? 'On' : 'Off';
            $old_title = $old_value == 1 ? 'On' : 'Off';
            if ($new_value || $new_value == 0) {
                if ($old_value) {
                    return lang('Is Enabled flag changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_title, 'new_value' => $new_title], true, $language);
                } else {
                    return lang('Is Enabled flag set to <b>:new_value</b>', ['new_value' => $new_title], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Is Enabled flag set to empty value', null, true, $language);
                }
            }
        };
        $renderers['auto_issue'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Auto Issue flag changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Auto Issue flag set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Auto Issue flag set to empty value', null, true, $language);
                }
            }
        };
        $renderers['email_subject'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Email subject changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Email subject set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Email subject set to empty value', null, true, $language);
                }
            }
        };
        $renderers['email_body'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Email content updated', null, true, $language);
                } else {
                    return lang('Email content added', null, true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Email content removed', null, true, $language);
                }
            }
        };
        $renderers['recipients'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Recipients changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Recipients set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Recipients set to empty value', null, true, $language);
                }
            }
        };
        $renderers['status'] = function ($old_value, $new_value, Language $language) {
            if ($new_value) {
                if ($old_value) {
                    return lang('Status changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
                } else {
                    return lang('Status set to <b>:new_value</b>', ['new_value' => $new_value], true, $language);
                }
            } else {
                if ($old_value) {
                    return lang('Status set to empty value', null, true, $language);
                }
            }
        };
    }
}
