<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to {{ $appName }}</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f5f7; color: #333333; margin: 0; padding: 0;">

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; margin: 40px auto; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #eef0f3;">

        <tr>
            <td style="background: linear-gradient(135deg, #4f46e5, #6366f1); padding: 40px 20px; text-align: center;">
                @if(!empty($appLogo))
                    <img src="{{ $appLogo }}" alt="{{ $appName }} Logo" style="height: 50px; width: auto; margin-bottom: 12px; display: inline-block; vertical-align: middle;">
                @endif
                <h1 style="margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; color: #ffffff;">
                    Welcome to {{ $appName }}! 🚀
                </h1>
            </td>
        </tr>

        <tr>
            <td style="padding: 40px 30px; line-height: 1.6;">
                <h2 style="color: #1f2937; font-size: 20px; margin-top: 0; font-weight: 700;">Hi {{ $user->name }},</h2>
                <p style="color: #4b5563; font-size: 15px; margin-bottom: 16px;">
                    Thank you for joining us! We are thrilled to help you take control of your financial journey, track your expenses seamlessly, and achieve your saving goals effortlessly.
                </p>
                <p style="color: #4b5563; font-size: 15px; margin-bottom: 24px;">
                    Your account is now active. Get started by exploring your personalized dashboard, setting up your monthly budget tracks, and monitoring your financial progress in real-time.
                </p>

                <table align="center" border="0" cellpadding="0" cellspacing="0" style="margin: 30px auto; text-align: center;">
                    <tr>
                        <td align="center" bgcolor="#4f46e5" style="border-radius: 8px;">
                            <a href="{{ config('app.frontend_url') }}/dashboard" target="_blank" style="display: inline-block; padding: 12px 30px; color: #ffffff !important; text-decoration: none; font-weight: 600; font-size: 15px; text-align: center;">
                                Go to Dashboard
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="background-color: #f9fafb; padding: 25px; text-align: center; font-size: 12px; color: #6b7280; border-top: 1px solid #f3f4f6;">
                <p style="margin: 0 0 8px 0;">&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
                <p style="margin: 0;">If you have any questions, feel free to reply to this email or use our platform support system.</p>
            </td>
        </tr>

    </table>

</body>
</html>
