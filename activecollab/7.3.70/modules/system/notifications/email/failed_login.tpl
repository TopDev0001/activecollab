{lang language=$language}Failed Login{/lang}
================================================================================
{if $username}<p>{lang username=$username language=$language}User <b>:username</b> is having hard time logging in{/lang}.</p>{/if}
<p>{lang max_attempts=$max_attempts minutes=$cooldown_in_minutes from_ip=$from_ip language=$language}There are at least :max_attempts failed login attempts in the past :minutes minutes from :from_ip{/lang}.</p>