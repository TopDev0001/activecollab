{lang name=$profile->getName() language=$language}:name: New Invoice draft created{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang language=$language}A recurring invoice draft has been created:{/lang}
</h1>

<p>
    <strong>{lang language=$language}Recurring profile{/lang}:</strong> {$profile->getName()} <br/>
    <strong>{lang language=$language}Amount{/lang}:</strong> {$context->getBalanceDue()|money:$context->getCurrency():$language:true}
    <br/>
    <strong>{lang language=$language}Frequency{/lang}:</strong> {$profile->getVerboseFrequency($language)} <br/>
</p>

<p><a href="{$context->getViewUrl()}">{lang language=$language}Click here to send the invoice{/lang}</a></p>

<p>{lang recurring_profile_url=$profile->getViewUrl() language=$language}This invoice is set to be issued manually. To change the amount, frequency and sending options, <a href=":recurring_profile_url">edit the recurring profile settings</a>.{/lang}</p>

<!-- Metadata -->
<div class="metadata" style="color: #999999; font-size: 14px; line-height: 21px;">
    <p>{lang language=$language}Notification sent to{/lang} {Invoices::findFinancialManagers()|notification_recipients:$sender:'color: #999999; text-decoration: none;':$language nofilter}</p>
</div>