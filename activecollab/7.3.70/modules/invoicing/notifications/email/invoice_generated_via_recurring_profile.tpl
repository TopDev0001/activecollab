{lang language=$language}Invoice has been Created and Issued{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang language=$language}The following recurring invoice has been sent:{/lang}
</h1>
<p>
    <strong>{lang language=$language}Recurring profile{/lang}:</strong> {$profile->getName()} <br/>
    {if $invoice_recipients && is_foreachable($invoice_recipients)}
        <strong>{lang language=$language}Sent to{/lang}:</strong>
        {foreach from=$invoice_recipients item=invoice_recipient name=invoice_recipients}
            <a href="mailto:{$invoice_recipient->getEmail()}">{$invoice_recipient->getEmail()}</a>{if !$smarty.foreach.invoice_recipients.last}, {/if}
        {/foreach}
        <br/>
    {/if}
    <strong>{lang language=$language}Amount{/lang}:</strong> {$context->getBalanceDue()|money:$context->getCurrency():$language:true}
    <br/>
    <strong>{lang language=$language}Due on{/lang}:</strong> {$context->getDueOn()->formatForUser($recipient, 0, $language)}
    <br/>
    <strong>{lang language=$language}Frequency{/lang}:</strong> {$profile->getVerboseFrequency($language)}
</p>

<p><a href="{$context->getViewUrl()}">{lang language=$language}View the invoice{/lang}</a></p>
<p>{lang recurring_profile_url=$profile->getViewUrl() language=$language}This invoice was created and sent automatically. To change the amount, frequency and sending options, <a href=":recurring_profile_url">edit the recurring profile settings</a>.{/lang}</p>

<!-- Metadata -->
<div class="metadata" style="color: #999999; font-size: 14px; line-height: 21px;">
    <p>{lang language=$language}Notification sent to{/lang} {Invoices::findFinancialManagers()|notification_recipients:$sender:'color: #999999; text-decoration: none;':$language nofilter}</p>
</div>