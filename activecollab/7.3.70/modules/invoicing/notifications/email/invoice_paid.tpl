{lang num=$context->getName() client=$context->getCompanyName() language=$language}Invoice Paid in full – #:num for :client{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang language=$language}The following invoice has been paid:{/lang}<br>
</h1>

<p>
    <strong>{lang language=$language}Invoice No{/lang}:</strong> {$context->getNumber()} <br>
    <strong>{lang language=$language}Client{/lang}:</strong> {$context->getCompanyName()} <br>
    <strong>{lang language=$language}Amount{/lang}:</strong> {$context->getTotal()|money:$context->getCurrency():$language:true}
    <br>
</p>

<p><a href="{$context->getPublicUrl()}" rel="nofollow">{lang language=$language}View the invoice{/lang}</a></p>
<p>{lang language=$language}The invoice has been marked as "Paid" in ActiveCollab. It will be archived automatically after one month.{/lang}</p>

<!-- Metadata -->
<div class="metadata" style="color: #999999; font-size: 14px; line-height: 21px;">
    <p>{lang language=$language}Notification sent to{/lang} {$context->getRecipientInstances()|notification_recipients:$sender:'color: #999999; text-decoration: none;':$language nofilter}</p>
</div>
