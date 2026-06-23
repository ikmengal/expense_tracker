<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Support Ticket Resolved - CashFlow App</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f5f7; color: #333333; margin: 0; padding: 0; }
        .email-container { max-width: 550px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #eef0f3; }
        .header { background: linear-gradient(135deg, #4f46e5, #6366f1); padding: 40px 20px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .content { padding: 40px 30px; line-height: 1.6; }
        .content h2 { color: #1f2937; font-size: 20px; margin-top: 0; font-weight: 700; }

        /* 📜 Original User Message styling block */
        .ticket-summary { background-color: #f8fafc; border-left: 4px solid #cbd5e1; padding: 15px; border-radius: 4px 12px 12px 4px; margin: 20px 0; font-size: 13px; }
        .ticket-label { font-weight: 700; color: #64748b; text-transform: uppercase; font-size: 11px; tracking-wide; }
        .ticket-text { color: #475569; italic; margin-top: 4px; }

        /* 💡 Admin Resolution response block */
        .resolution-box { background-color: #f5f3ff; border: 1px solid #e0e7ff; border-left: 4px solid #6366f1; padding: 20px; border-radius: 12px; margin: 25px 0; }
        .resolution-title { font-weight: 700; color: #4f46e5; font-size: 14px; margin-bottom: 6px; display: flex; items-center: center; }
        .resolution-text { color: #1e1b4b; font-size: 14px; white-space: pre-line; }

        .btn-container { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; padding: 12px 28px; background-color: #4f46e5; color: #ffffff !important; text-decoration: none; border-radius: 10px; font-weight: 600; text-align: center; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.15); transition: all 0.2s ease; font-size: 13px; }
        .footer { background-color: #f9fafb; padding: 25px; text-align: center; font-size: 12px; color: #6b7280; border-top: 1px solid #f3f4f6; }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header Section matching Password Reset -->
        <div class="header">
            <h1>📊 CashFlow App</h1>
        </div>

        <div class="content">
            <h2>Support Ticket Resolved</h2>
            <p>Hello <strong>{{ $ticket->user->name }}</strong>,</p>
            <p>Our technical administration team has reviewed and addressed the inquiry you submitted regarding your dashboard. Your ticket <strong>#{{ $ticket->id }}</strong> status has been updated to <strong>Resolved</strong>.</p>

            <!-- 📜 Context Holder for user reference -->
            <div class="ticket-summary">
                <div class="ticket-label">Your Original Message (Subject: {{ $ticket->subject }})</div>
                <div class="ticket-text">"{{ $ticket->message }}"</div>
            </div>

            <!-- 💡 Official Admin Solution Message Content -->
            <div class="resolution-box">
                <div class="resolution-title">🛠️ Official Resolution / Response:</div>
                <div class="resolution-text">{{ $replyMessage }}</div>
            </div>

            <p>If your system anomaly or query is fixed, no further action is needed. If you require further clarification, you can visit your control log directly.</p>

            <!-- Button Wrapper -->
            <div class="btn-container">
                <a href="{{ url('/dashboard') }}" class="btn">Go to Dashboard</a>
            </div>

            <p style="font-size: 12px; color: #9ca3af; margin-top: 25px;">Thank you for your patience while we secured this operational patch for your account routing layout.</p>
        </div>

        <!-- Footer System Copyrights -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} CashFlow App. All rights reserved.</p>
            <p>Secure Wealth & Transaction Management System.</p>
        </div>
    </div>
</body>
</html>
