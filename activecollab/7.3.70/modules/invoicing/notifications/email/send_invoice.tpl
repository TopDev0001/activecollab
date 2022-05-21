{if $custom_subject}
    {$custom_subject nofilter}
{else}
    {lang company_name=$owner_company->getName() language=$language}Invoice from :company_name{/lang}
{/if}
================================================================================

{if $custom_message}
    <p>{$custom_message|escape|nl2br nofilter}</p>
{else}
    <h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
        {lang company_name=$owner_company->getName() language=$language}Invoice from :company_name.{/lang}<br/>
    </h1>
{/if}

<p>
    <strong>{lang language=$language}Invoice No{/lang}:</strong> {$context->getNumber()} <br>
    <strong>{lang language=$language}Client{/lang}:</strong> {$context->getCompanyName()} <br>
    <strong>{lang language=$language}Amount{/lang}:</strong> {$context->getBalanceDue()|money:$context->getCurrency():$language:true}
    <br>
    <strong>{lang language=$language}Due on{/lang}:</strong> {$context->getDueOn()->formatForUser($recipient, 0, $language)}
</p>

{if $context->canMakePayment()}
    <p><a href="{$context->getPublicUrl($recipient)}" rel="nofollow">{lang language=$language}Click here to make the payment{/lang}</a></p>
{/if}

<p>{lang language=$language}To view the invoice details, open the attached PDF.{/lang}</p>

<div class="metadata" style="color: #999999; font-size: 14px; line-height: 21px;">
    <p>{lang sender_name=$sender->getDisplayName() company_name=$owner_company->getName() language=$language}Sent by :sender_name from :company_name to{/lang} {$context->getRecipientInstances()|notification_recipients:$sender:'color: #999999; text-decoration: none;':$language nofilter}</p>
</div>
