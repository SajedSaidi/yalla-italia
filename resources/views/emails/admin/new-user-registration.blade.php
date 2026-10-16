<x-mail::message>
# New registration awaiting approval

A new {{ $user->role }} has just registered and is waiting for approval before they can sign in.

<x-mail::panel>
**Name:** {{ $user->name }}<br>
**Email:** {{ $user->email }}<br>
@if ($user->student?->phone)
**Phone:** {{ $user->student->phone }}<br>
@endif
@if ($user->student?->nationality)
**Nationality:** {{ $user->student->nationality->name }}<br>
@endif
**Registered:** {{ $user->created_at->format('M d, Y \a\t H:i') }}
</x-mail::panel>

<x-mail::button :url="$approvalUrl">
Review & approve
</x-mail::button>

The student will be emailed automatically as soon as their account is approved.

Thanks,<br>
{{ config('mail.from.name') }} portal
</x-mail::message>
