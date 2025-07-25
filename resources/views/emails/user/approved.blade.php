<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Approved</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #059669;">Account Approved!</h2>
        
        <p>Dear {{ $user->name }},</p>
        
        <p>Great news! Your account has been approved and you can now access the Yalla Italia system.</p>
        
        <div style="background: #f0fdf4; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #059669;">
            <strong>Email:</strong> {{ $user->email }}<br>
            <strong>Role:</strong> {{ ucfirst($user->role) }}
        </div>
        
        <p>You can now log in to your account using the credentials you provided during registration.</p>
        
        <a href="{{ $loginUrl }}" style="display: inline-block; background: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Login Now
        </a>
        
        <p>If you have any questions, please don't hesitate to contact us.</p>
        
        <p>Welcome to Yalla Italia!</p>
    </div>
</body>
</html>