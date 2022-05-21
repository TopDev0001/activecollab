{lang language=$language}Payment received{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
  {lang language=$language}Thank you. Your payment of {$context->getAmount()|money:$context->getCurrency():$language:true} has been successfully received.{/lang}<br>
</h1>

<p><a href="{$context->getParent()->getPublicUrl()}" rel="nofollow">{lang language=$language}View the invoice{/lang}</a></p>
