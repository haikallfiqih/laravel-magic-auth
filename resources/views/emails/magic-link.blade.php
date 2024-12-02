<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your Magic Link</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Login to Your Account</h2>
        <p>Click the button below to securely log in to your account. This link will expire in {{ config('magic-auth.link_expiration') }} minutes.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $magicLink }}" 
               style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
                Login to Your Account
            </a>
        </div>

        <p>If you didn't request this login link, you can safely ignore this email.</p>
        
        <p>If the button doesn't work, copy and paste this link into your browser:</p>
        <p style="word-break: break-all;">{{ $magicLink }}</p>
    </div>
</body>
</html>
