<x-mail::message>
# Documents needed for your application

Hi {{ $studentName }},

To keep your application{{ $missingDocs->pluck('university')->unique()->count() > 1 ? 's' : '' }} moving, we still need the following **documents** from you:

<x-mail::table>
| Document | University | Level / Year | Deadline |
|:---------|:-----------|:-------------|:---------|
@foreach ($missingDocs as $doc)
| **{{ $doc['document_name'] }}** | {{ $doc['university'] }} | {{ $doc['education_level'] }}<br>{{ $doc['academic_year'] }} | {{ $doc['deadline'] }} |
@endforeach
</x-mail::table>

<x-mail::button :url="url('/admin')">
Upload my documents
</x-mail::button>

Uploading them early gives us time to review everything before the deadline.

Thanks,<br>
The {{ config('mail.from.name') }} admissions team
</x-mail::message>
