{lang company_name=$owner_company->getName() language=$language}Reminder for unpaid Invoice from :company_name{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang company_name=$owner_company->getName() language=$language}Invoice overdue reminder from :company_name.{/lang}
    <br/>
</h1>

<p>
    <strong>{lang language=$language}Invoice No{/lang}:</strong> {$context->getNumber()} <br>
    <strong>{lang language=$language}Client{/lang}:</strong> {$context->getCompanyName()} <br>
    <strong>{lang language=$language}Balance Due{/lang}:</strong> {$context->getBalanceDue()|money:$context->getCurrency():$language:true}
    <br>
    <strong>{lang language=$language}Due on{/lang}:</strong> {$context->getDueOn()->formatForUser($recipient, 0, $language)}
    <span style="color: #ff0000">({lang language=$language}{$overdue_days} day(s) overdue{/lang})</span>
</p>

<p><a href="{$context->getPublicUrl()}" rel="nofollow">{lang language=$language}Click here to make the payment{/lang}</a></p>
<p>{$additional_message|escape|nl2br nofilter}</p>