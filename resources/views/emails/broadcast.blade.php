<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
    <style>
        body {
            font-family: 'Instrument Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-w-600px;
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05);
        }
        .header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 24px 32px;
            text-align: left;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .header p {
            margin: 4px 0 0 0;
            font-size: 12px;
            color: #94a3b8;
        }
        .content {
            padding: 32px;
            line-height: 1.6;
            font-size: 14px;
            color: #334155;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 16px 32px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">    
            <!-- <h1>Sekretariat Universitas</h1>
            <p>Repositori Sistem Sekretariat Universitas</p> -->
        </div>
        <div class="content">
            <h2 style="margin-top: 0; font-size: 16px; color: #0f172a;">{{ $emailSubject }}</h2>
            <div style="font-size: 14px; line-height: 1.6; color: #334155;">
                {!! $emailBody !!}
            </div>
            <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 24px 0;">
            <p style="margin-bottom: 0; font-size: 12px; color: #64748b;">
                Pesan ini dikirim secara resmi oleh <strong>{{ $senderName }}</strong> melalui Sistem Repositori Sekretariat Perusahaan.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Corporate Secretariat Repository System. All rights reserved.
        </div>
    </div>
</body>
</html>
