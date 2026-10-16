<x-mail::message>
# Your account is approved 🎉

Hi {{ $user->name }},

Great news! Your {{ config('mail.from.name') }} account has been reviewed and approved. You can now sign in to the student portal to upload your documents, follow your applications and stay on top of your deadlines.

<x-mail::panel>
**Email:** {{ $user->email }}<br>
**Account type:** {{ ucfirst($user->role) }}
</x-mail::panel>

<x-mail::button :url="$loginUrl">
Sign in to the portal
</x-mail::button>

Use the password you chose when registering. If you've forgotten it, use **Forgot password?** on the sign-in page.

Welcome aboard,<br>
The {{ config('mail.from.name') }} team
</x-mail::message>
