{lang language=$language}Estimate updated{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang estimate_name=$context->getName() company_name=$owner_company->getName() language=$language}Estimate updated for :estimate_name from :company_name.{/lang}
    <br/>
    {lang language=$language}Amount{/lang}: <span style="color:#999999">{$old_total|money:$context->getCurrency():$language:true:true}</span> {$context->getTotal()|money:$context->getCurrency():$language:true:true}
</h1>
<p><a href="{$context->getPublicUrl()}" rel="nofollow">{lang language=$language}View the updated estimate{/lang}</a></p>
<p>{lang language=$language}To view the updated estimate, visit the above link or open the attached PDF. You can discuss the estimate by replying directly to this email.{/lang}</p>

<!-- Metadata -->
<div class="metadata" style="color: #999999; font-size: 14px; line-height: 21px;">
    <p>{lang sender_name=$sender->getDisplayName() company_name=$owner_company->getName() language=$language}Sent by :sender_name from :company_name to{/lang} {$context->getRecipientInstances()|notification_recipients:$sender:'color: #999999; text-decoration: none;':$language nofilter}</p>
</div>