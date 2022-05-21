Message from ActiveCollab
================================================================================

<div style="background: #FFF7C4; padding-top: 0; padding-bottom: 0; padding-left: 12px; padding-right: 12px; border-width: 1px; border-color: #E3DA9C; border-style: solid;">
    <p>{$comment|clean|nl2br nofilter}</p>

    <ul>
        {foreach $details as $detail => $value}
            <li><strong>{$detail}:</strong>
                {if is_bool($value)}
                    {if $value}Yes{else}No{/if}
                {else}
                    {$value}
                {/if}
            </li>
        {/foreach}
    </ul>
</div>
