<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Your Password - CashFlow App</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f5f7; color: #333333; margin: 0; padding: 0; }
        .email-container { max-width: 550px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #eef0f3; }
        .header { background: linear-gradient(135deg, #4f46e5, #6366f1); padding: 40px 20px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .content { padding: 40px 30px; line-height: 1.6; }
        .content h2 { color: #1f2937; font-size: 20px; margin-top: 0; font-weight: 700; }
        .btn-container { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; padding: 14px 32px; background-color: #4f46e5; color: #ffffff !important; text-decoration: none; border-radius: 10px; font-weight: 600; text-align: center; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2); transition: all 0.2s ease; }
        .footer { background-color: #f9fafb; padding: 25px; text-align: center; font-size: 12px; color: #6b7280; border-top: 1px solid #f3f4f6; }
        .break-word { word-break: break-all; color: #6366f1; font-size: 12px; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>📊 CashFlow App</h1>
        </div>

        <div class="content">
            <h2>Password Reset Request</h2>
            <p>Hello,</p>
            <p>We received a request to reset the password for your account associated with this email address. No problem, we've got you covered!</p>
            <p>Click the secure link below to choose a brand new password and gain back control of your financial tracking dashboard:</p>

            <div class="btn-container">
                <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
            </div>

            <p><strong>Note:</strong> This verification link is strictly valid for a limited period only. If you did not request a password change, please ignore this communication safely.</p>

            <hr style="border: 0; border-top: 1px solid #eef0f3; margin: 30px 0;">
            <p class="text-muted" style="font-size: 12px; color: #9ca3af;">If you're having trouble clicking the button, copy and paste the URL below into your web browser:</p>
            <p class="break-word">{{ $resetUrl }}</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} CashFlow App. All rights reserved.</p>
            <p>Secure Wealth & Transaction Management System.</p>
        </div>
    </div>
</body>
</html>
