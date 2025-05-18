<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Missing Documents Notification</title>
  <style>
    .container { width:100%; max-width:600px; margin:auto; font-family:Arial,sans-serif; }
    .header  { padding:20px 0; text-align:center; }
    .header h1 { margin:0; }
    .intro   { padding:0 20px; }
    table    { width:100%; border-collapse:collapse; margin-top:20px; }
    th, td   { padding:12px; border:1px solid #ddd; text-align:left; font-size:14px; }
    th       { background:#f5f5f5; }
    .button  { display:inline-block; margin:20px; padding:12px 24px; background:#286dbd; color:#fff; text-decoration:none; border-radius:4px; }
    .footer  { padding:20px; font-size:12px; color:#666; text-align:center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Dear {{ $studentName }},</h1>
      <p>You have the following <strong>missing documents</strong> for your application(s):</p>
    </div>

    <div class="intro">
      <table role="presentation">
        <thead>
          <tr>
            <th>Education Level</th>
            <th>University</th>
            <th>Academic Year</th>
            <th>Document</th>
            <th>Deadline</th>
          </tr>
        </thead>
        <tbody>
          @foreach($missingDocs as $doc)
            <tr>
              <td>{{ $doc['education_level'] }}</td>
              <td>{{ $doc['university'] }}</td>
              <td>{{ $doc['academic_year'] }}</td>
              <td>{{ $doc['document_name'] }}</td>
              <td>{{ $doc['deadline'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>


    <div class="footer">
      <p>Thanks,<br>Yalla Italia Admissions Team</p>
      <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
