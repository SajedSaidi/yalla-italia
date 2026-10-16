<x-mail::message>
# Reset your password

Hi {{ $name }},

We received a request to reset the password for your {{ config('mail.from.name') }} account. Click the button below to choose a new one.

<x-mail::button :url="$url">
Reset password
</x-mail::button>

<x-mail::panel>
This link expires in **{{ $expires }} minutes**. If you didn't ask for a new password, you can safely ignore this email: your current password stays the same.
</x-mail::panel>

Thanks,<br>
The {{ config('mail.from.name') }} team

<x-slot:subcopy>
If the "Reset password" button doesn't work, copy and paste this link into your browser:
<span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
