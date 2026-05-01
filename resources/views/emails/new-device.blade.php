<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Figtree', Arial, sans-serif; background: #f8fafc; margin: 0; padding: 32px; }
        .card { background: #fff; border-radius: 12px; padding: 32px; max-width: 520px; margin: 0 auto; border: 1px solid #e2e8f0; }
        .badge { display: inline-block; background: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 999px; margin-bottom: 20px; }
        h1 { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 8px; }
        p { color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 16px; }
        .info-row { display: flex; gap: 8px; margin-bottom: 8px; font-size: 14px; }
        .info-label { color: #94a3b8; width: 90px; flex-shrink: 0; }
        .info-value { color: #1e293b; font-weight: 600; }
        .btn { display: inline-block; background: #6366f1; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; margin-top: 24px; }
        .footer { margin-top: 24px; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">Action Required</div>
        <h1>New Device Registration</h1>
        <p>A dealer is trying to access the desktop app from an unrecognised device. Review and approve or reject below.</p>

        <div class="info-row"><span class="info-label">Device</span><span class="info-value">{{ $deviceName }}</span></div>
        <div class="info-row"><span class="info-label">Platform</span><span class="info-value">{{ $platform }}</span></div>
        <div class="info-row"><span class="info-label">Dealer</span><span class="info-value">{{ $dealerName }}</span></div>
        <div class="info-row"><span class="info-label">Time</span><span class="info-value">{{ now()->format('d M Y, h:i A') }}</span></div>

        <a href="{{ url('/owner/devices') }}" class="btn">Review in Dashboard →</a>

        <p class="footer">
            If you did not expect this request, reject it immediately. The device will be blocked from accessing your app.
        </p>
    </div>
</body>
</html>
