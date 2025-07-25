<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New User Registration</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">New User Registration - Approval Required</h2>
        
        <p>A new user has registered and requires approval:</p>
        
        <div style="background: #f8fafc; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <strong>Name:</strong> {{ $user->name }}<br>
            <strong>Email:</strong> {{ $user->email }}<br>
            <strong>Role:</strong> {{ ucfirst($user->role) }}<br>
            <strong>Registration Date:</strong> {{ $user->created_at->format('M d, Y \a\t H:i') }}
        </div>
        
        <p>Please review and approve this user to grant them access to the system.</p>
        
        <a href="{{ $approvalUrl }}" style="display: inline-block; background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Review User
        </a>
    </div>
</body>
</html>