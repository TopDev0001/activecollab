{if $custom_subject}
    {$custom_subject nofilter}
{else}
    {lang name=$context->getName() language=$language}Estimate ':name' has been Sent{/lang}
{/if}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang estimate_name=$context->getName() company_name=$owner_company->getName() language=$language}Estimate for :estimate_name from :company_name.{/lang}
    <br/>
    {lang language=$language}Amount{/lang}: {$context->getTotal()|money:$context->getCurrency():$language:true:true}
</h1>

{if $custom_message}
    <p>{$custom_message|escape|nl2br nofilter}</p>
{/if}

<!-- Metadata -->
<div class="metadata" style="color: #999999; font-size: 14px; line-height: 21px;">
    <p>{lang sender_name=$sender->getDisplayName() company_name=$owner_company->getName() language=$language}Sent by :sender_name from :company_name to{/lang} {$estimate_recipients|notification_recipients:$sender:'color: #999999; text-decoration: none;':$language nofilter}</p>
</div>
