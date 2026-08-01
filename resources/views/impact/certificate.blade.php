<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Certificate — {{ $user->name }} — {{ config('app.name') }}</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: 'Segoe UI', system-ui, sans-serif;
                background: #f6f9f6;
                padding: 40px 16px;
            }
            .sheet {
                max-width: 760px;
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
                padding: 20px 32px;
                background: #0f172a;
                color: #fff;
            }
            .brand { display: flex; align-items: center; gap: 12px; }
            .brand-mark {
                width: 40px; height: 40px; border-radius: 10px;
                background: #2e7d32; display: flex; align-items: center;
                justify-content: center; font-weight: 800; font-size: 20px;
            }
            .brand small { display: block; color: #94a3b8; font-size: 10px; letter-spacing: 2px; text-transform: uppercase; }
            .cert-label { font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: #fbc02d; }
            .body { padding: 40px 48px 32px; text-align: center; }
            .title { font-size: 30px; font-weight: 800; color: #0f172a; }
            .recipient { font-size: 26px; font-weight: 700; color: #2e7d32; margin-top: 8px; }
            .subtitle { margin-top: 12px; color: #475569; font-size: 14px; line-height: 1.6; }
            .stats { display: flex; gap: 16px; justify-content: center; margin-top: 28px; flex-wrap: wrap; }
            .stat {
                flex: 1; min-width: 150px; border: 1px solid #e2e8f0;
                border-radius: 12px; padding: 16px 12px; background: #f6f9f6;
            }
            .stat b { display: block; font-size: 22px; color: #0f172a; }
            .stat span { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
            .footer {
                display: flex; align-items: center; justify-content: space-between;
                gap: 24px; padding: 24px 48px; border-top: 1px dashed #cbd5e1;
                background: #fff;
            }
            .qr img, .qr { width: 120px; height: 120px; }
            .meta { flex: 1; font-size: 12px; color: #64748b; line-height: 1.7; }
            .meta code { background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 11px; }
            .stamp {
                text-align: center; border: 2px solid #2e7d32; color: #2e7d32;
                border-radius: 8px; padding: 8px 14px; font-weight: 700;
                font-size: 12px; transform: rotate(-6deg); letter-spacing: 1px;
            }
            .actions { max-width: 760px; margin: 20px auto 0; text-align: right; }
            .actions button {
                border: none; background: #2e7d32; color: #fff; font-weight: 600;
                padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px;
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
                <div class="cert-label">Certificate of Community Impact</div>
            </div>

            <div class="body">
                <p class="title">{{ $type === 'co2' ? 'CO₂ SAVED' : 'FUEL SAVED' }} CERTIFICATE</p>
                <p class="recipient">{{ $user->name }}</p>
                <p class="subtitle">
                    @if ($type === 'co2')
                        This certifies that the above-named verified civil servant shared rides on
                        WorkRide and saved <b>{{ number_format((float) $stat->co2_saved_kg, 1) }} kg of CO₂</b> —
                        equivalent to {{ number_format((float) $stat->trees_equivalent, 1) }} trees planted.
                    @else
                        This certifies that the above-named verified civil servant shared rides on
                        WorkRide and saved <b>{{ number_format((float) $stat->fuel_saved_litres, 1) }} litres of fuel</b>
                        — money kept in the worker's pocket during the fuel crisis.
                    @endif
                </p>

                <div class="stats">
                    <div class="stat">
                        <b>{{ $stat->total_trips }}</b>
                        <span>Shared rides</span>
                    </div>
                    <div class="stat">
                        <b>{{ $type === 'co2' ? number_format((float) $stat->co2_saved_kg, 1).' kg' : number_format((float) $stat->fuel_saved_litres, 1).' L' }}</b>
                        <span>{{ $type === 'co2' ? 'CO₂ saved' : 'Fuel saved' }}</span>
                    </div>
                    <div class="stat">
                        <b>#{{ $rank }}</b>
                        <span>Abuja-wide rank</span>
                    </div>
                    <div class="stat">
                        <b>{{ $percentile }}%</b>
                        <span>Green percentile</span>
                    </div>
                </div>
            </div>

            <div class="footer">
                <div class="qr">
                    <img src="{{ $qrDataUri }}" alt="Verify this certificate">
                </div>
                <div class="meta">
                    <strong>Verifiable certificate</strong><br>
                    Scan the QR or visit:<br>
                    <code>{{ $verifyUrl }}</code><br>
                    Issued: {{ $issuedAt->format('d M Y, H:i') }} · Green Level {{ $stat->level }}/5
                </div>
                <div class="stamp">
                    WORKRIDE<br>
                    VERIFIED
                </div>
            </div>
        </div>
    </body>
</html>
