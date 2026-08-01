<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Receipt') — {{ config('app.name') }}</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: 'Segoe UI', system-ui, sans-serif;
                background: #f6f9f6;
                padding: 40px 16px;
            }
            .sheet {
                max-width: 720px;
                margin: 0 auto;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                overflow: hidden;
            }
            .topbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 18px 28px;
                background: #0f172a;
                color: #fff;
            }
            .brand { display: flex; align-items: center; gap: 12px; }
            .brand-mark {
                width: 36px; height: 36px; border-radius: 10px;
                background: #2e7d32; display: flex; align-items: center;
                justify-content: center; font-weight: 800; font-size: 18px;
            }
            .brand small { display: block; color: #94a3b8; font-size: 10px; letter-spacing: 2px; text-transform: uppercase; }
            .receipt-label { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #fbc02d; }
            .body { padding: 28px 36px 8px; }
            .title { font-size: 24px; font-weight: 800; color: #0f172a; }
            .holder { font-size: 18px; font-weight: 700; color: #2e7d32; margin-top: 4px; }
            .ref { margin-top: 6px; font-size: 12px; color: #64748b; }
            .ref code { background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 11px; }
            .rows { margin-top: 20px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
            .row { display: flex; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
            .row:last-child { border-bottom: none; background: #f6f9f6; font-weight: 700; }
            .row span:first-child { color: #64748b; }
            .row span:last-child { color: #0f172a; text-align: right; }
            .footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
                padding: 22px 36px;
                border-top: 1px dashed #cbd5e1;
                background: #fff;
                margin-top: 24px;
            }
            .qr img, .qr { width: 100px; height: 100px; }
            .meta { flex: 1; font-size: 12px; color: #64748b; line-height: 1.7; }
            .meta code { background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 11px; }
            .stamp {
                text-align: center; border: 2px solid #2e7d32; color: #2e7d32;
                border-radius: 8px; padding: 8px 14px; font-weight: 700;
                font-size: 12px; transform: rotate(-6deg); letter-spacing: 1px;
            }
            .actions { max-width: 720px; margin: 20px auto 0; text-align: right; }
            .actions button {
                border: none; background: #2e7d32; color: #fff; font-weight: 600;
                padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px;
            }
            .actions a {
                display: inline-block; margin-right: 8px; text-decoration: none;
                background: #f1f5f9; color: #0f172a; font-weight: 600;
                padding: 10px 20px; border-radius: 8px; font-size: 14px;
            }
            @media print {
                body { background: #fff; padding: 0; }
                .actions { display: none; }
                .sheet { border: none; border-radius: 0; }
            }
        </style>
    </head>
    <body>
        <div class="actions">
            <a href="{{ $verifyUrl }}" target="_blank">Verify online</a>
            <button onclick="window.print()">Print / Save PDF</button>
        </div>

        <div class="sheet">
            <div class="topbar">
                <div class="brand">
                    <span class="brand-mark">W</span>
                    <div>
                        <strong>WorkRide</strong>
                        <small>Community Transit Intelligence</small>
                    </div>
                </div>
                <div class="receipt-label">@yield('label')</div>
            </div>

            <div class="body">
                <p class="title">@yield('title')</p>
                <p class="holder">{{ $holder }}</p>
                <p class="ref">Reference: <code>{{ $reference }}</code></p>

                <div class="rows">
                    @yield('rows')
                </div>
            </div>

            <div class="footer">
                <div class="qr">
                    <img src="{{ $qrDataUri }}" alt="Verify this receipt">
                </div>
                <div class="meta">
                    <strong>Verifiable receipt</strong><br>
                    Scan the QR or visit:<br>
                    <code>{{ $verifyUrl }}</code><br>
                    Issued: {{ $issuedAt->format('d M Y, H:i') }}
                </div>
                <div class="stamp">
                    WORKRIDE<br>
                    VERIFIED
                </div>
            </div>
        </div>
    </body>
</html>
