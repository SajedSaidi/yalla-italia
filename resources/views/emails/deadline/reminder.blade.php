<x-mail::message>
# Upcoming application deadlines

Hi {{ $studentName }},

This is a friendly reminder that the following application{{ $programs->count() > 1 ? 's are' : ' is' }} still **unpaid** and the deadline is getting close:

<x-mail::table>
| Program | Deadline | Fee |
|:--------|:---------|----:|
@foreach ($programs as $program)
| {{ $program['title'] }} | {{ $program['deadline']->format('M j, Y') }}<br>**{{ $program['days_left'] }} day{{ $program['days_left'] == 1 ? '' : 's' }} left** | €{{ number_format((float) $program['fee'], 2) }} |
@endforeach
</x-mail::table>

<x-mail::button :url="url('/admin')">
Open my applications
</x-mail::button>

If you have already paid, please let us know so we can update your application.

Thanks,<br>
The {{ config('mail.from.name') }} admissions team
</x-mail::message>
