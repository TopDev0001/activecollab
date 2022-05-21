{if $custom_subject}
  {$custom_subject nofilter}
{else}
  {lang name=$context->getName() language=$language}Info{/lang}
{/if}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">{lang language=$language}Info{/lang}</h1>

{if $custom_message}
  <div style="background: #FFF7C4; padding-top: 0; padding-bottom: 0; padding-left: 12px; padding-right: 12px; border-width: 1px; border-color: #E3DA9C; border-style: solid;">
    <p>{$custom_message nofilter}</p>
  </div>
{/if}