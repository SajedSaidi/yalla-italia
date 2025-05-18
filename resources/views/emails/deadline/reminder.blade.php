<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Application Deadlines Reminder</title>
  <style>
    /* Embedded CSS for email (most clients will inline these) */
    .container {
      width: 100%;
      max-width: 600px;
      margin: 0 auto;
      font-family: Arial, sans-serif;
      color: #333333;
    }
    .header {
      padding: 20px;
      text-align: center;
    }
    .header img {
      max-height: 48px;
    }
    .content {
      padding: 20px;
    }
    .program {
      border-bottom: 1px solid #eaeaea;
      padding: 12px 0;
    }
    .button {
      display: inline-block;
      margin-top: 20px;
      padding: 12px 24px;
      background-color: #286dbd;
      color: #ffffff !important;
      text-decoration: none;
      border-radius: 4px;
    }
    .footer {
      padding: 20px;
      font-size: 12px;
      color: #888888;
      text-align: center;
    }
  </style>
</head>
<body>
  <table class="container" role="presentation" cellpadding="0" cellspacing="0">
    <tr>
      <td class="header">
        {{-- <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}"> --}}
      </td>
    </tr>
    <tr>
      <td class="content">
        <h1>Dear {{ $studentName }},</h1>
        <p>This is a friendly reminder about your upcoming application deadlines:</p>

        @foreach ($programs as $program)
          <div class="program">
            <p><strong>{{ $program['title'] }}</strong></p>
            <p>
              Deadline: {{ $program['deadline']->format('F j, Y') }}  
              ({{ $program['days_left'] }} days left)<br>
              Application Fee: €{{ number_format($program['fee'], 2) }}
            </p>
          </div>
        @endforeach
      </td>
    </tr>
    <tr>
      <td class="footer">
        <p>Thanks,<br>Yalla Italia Admissions Team</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
      </td>
    </tr>
  </table>
</body>
</html>
