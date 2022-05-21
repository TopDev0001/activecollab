{if $context instanceof IProjectElement && $context->getProject() instanceof Project}[{$context->getProject()->getName()}] {/if}Re: {$context->getName()}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
	{lang language=$language}New comment posted in:{/lang}<br />
	<a style="display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 550px;" href="{$context->getViewUrl()}">{$context->getName()}</a>
</h1>


<!-- Comment table -->
<table border="0" cellpadding="10" cellspacing="0" bgcolor="#ffffff" style="width: 100%">
	{foreach from=$latest_comments item=comm name=comments}
		{assign var=comm_author value=$comm->getCreatedBy()}

		<tr>
			<td valign="top" style="padding-left: 0; width: 36px;">
				<img src="{$comm_author->getAvatarUrl(36)}" width="36" height="36" alt="{$comm_author->getDisplayName()}">
			</td>
			<td colspan="2" valign="top" style="padding-left: 0;{if !$smarty.foreach.comments.first} color:#999999;{/if}">
				<strong>{$comm_author->getDisplayName()}</strong> <span style="font-size: 14px; line-height: 14px;">&nbsp;{$comm->getCreatedOn()->formatDateForUser($recipient, null, $language)}</span> <br />
				<span style="word-wrap: break-word; word-break: break-word; -ms-word-break: break-all; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto;">{$comm->getFormattedBody('email')|jwt_signed_file:$recipient nofilter}</span>
				{notification_attachments_table object=$comm recipient=$recipient}
			</td>
		</tr>

		{if $smarty.foreach.comments.first && $total_comments > 1}
			<tr><!-- Divider -->
				<td colspan="2" height="1" bgcolor="#cccccc" style="height: 1px; font-size: 0; line-height: 0; padding: 0;">&nbsp;</td>
			</tr>

			<tr>
				<td valign="top" style="padding-bottom: 0;">
					&nbsp;
				</td>
				<td valign="top" style="padding-left: 0; padding-bottom: 0; padding-top: 14px; font-size: 14px; line-height: 14px; font-weight: bold;">
					{lang language=$language}Older comments{/lang}
				</td>
			</tr>
		{/if}
	{/foreach}

{if $total_comments > 1}
	<tr><!-- Divider -->
		<td colspan="2" height="1" bgcolor="#cccccc" style="height: 1px; font-size: 0; line-height: 0; padding: 0;">&nbsp;</td>
	</tr>

	{if $total_comments > 5}
	<tr>
		<td colspan="2" style="padding-left: 0; padding-bottom: 16px; padding-top: 14px; color:#777777;" align="center">
			<a href="{$context->getViewUrl()}" style="font-size: 14px; line-height: 14px;">{lang total_comments=$total_comments language=$language}View all comments (:total_comments){/lang}</a>
		</td>
	</tr>
	{/if}
{/if}
</table>

<!-- Metadata -->
{notification_inspector context=$context recipient=$recipient link_style='color: #999999; text-decoration: none;'}
