{lang language=$language}Your export is ready{/lang}
================================================================================

<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang language=$language}Your export is ready{/lang}
</h1>
<p>{lang name=$user_name language=$language}Hello :name{/lang},</p>
<p>{lang account_id=$account_id language=$language}We've exported your data (account #:account_id). The download link will remain active for the next 48 hours:{/lang}</p>
<p><a href="{$download_url|jwt_signed_file:$recipient}">{lang size=$archive_size language=$language}Download archive (:size){/lang}</a></p>
{if $export_type == 'sql'}
    <p>{lang language=$language}When you're done, here's how to <a href="https://help.activecollab.com/books/self-hosted/installation.html#s-cloud-import">import the data to the self-hosted version</a>.{/lang}</p>
{/if}
