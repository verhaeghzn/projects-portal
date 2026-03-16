<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to TU/e Projects Portal</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #C8102E 0%, #A00D24 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #C8102E;
            margin-top: 0;
            font-size: 24px;
        }
        .content p {
            margin: 16px 0;
            color: #555;
            font-size: 16px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #C8102E;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #A00D24;
        }
        .footer {
            background-color: #f8f8f8;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        .footer p {
            margin: 8px 0;
            color: #777;
            font-size: 14px;
        }
        .logo-text {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo-text">TU/e</div>
            <h1>{{ config('app.name') }}</h1>
        </div>
        
        <div class="content">
            <h2>Welcome, {{ $user->name }}!</h2>
            
            <p>You have been invited to join the {{ config('app.name') }}. This platform allows you to publish and manage projects within our department.</p>
            
            <p>To get started, please complete your account setup by clicking the button below:</p>
            
            <div class="button-container">
                <a href="{{ $invitationUrl }}" class="button">Complete Your Registration</a>
            </div>
            
            <p>This invitation link will expire in 7 days. If you did not expect this invitation, you can safely ignore this email.</p>
        </div>
        
        <div class="footer">
            <p><strong>Eindhoven University of Technology</strong></p>
            <p>{{ config('app.name') }}</p>
            <p style="margin-top: 20px; font-size: 12px; color: #999;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
