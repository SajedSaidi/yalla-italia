@props(['url'])
<tr>
<td class="header">
<table class="flag-bar" width="570" align="center" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="flag-green" width="33%" height="5"></td>
<td class="flag-white" width="34%" height="5"></td>
<td class="flag-red" width="33%" height="5"></td>
</tr>
</table>
<a href="{{ $url }}" style="display: inline-block;">
{{-- Swapped for an inline (CID) attachment when the email is sent, see AppServiceProvider. --}}
<img src="{{ asset('images/logo-email.png') }}" class="logo" width="96" height="75" alt="{{ config('mail.from.name') }}">
</a>
<p class="brand-name">{{ $slot }}</p>
</td>
</tr>
