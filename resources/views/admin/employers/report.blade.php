<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>CSR Report — {{ $employer->name }} — {{ config('app.name') }}</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: 'Segoe UI', system-ui, sans-serif;
                background: #f6f9f6;
                padding: 40px 16px;
                color: #0f172a;
            }
            .sheet {
                max-width: 860px;
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
            .label { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #fbc02d; text-align: right; }
            .body { padding: 28px 36px; }
            .title { font-size: 24px; font-weight: 800; }
            .sub { margin-top: 6px; font-size: 14px; color: #64748b; }
            .sub strong { color: #2e7d32; }
            .grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 12px;
                margin-top: 24px;
            }
            .kpi {
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 14px 16px;
                background: #fff;
            }
            .kpi .k { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #64748b; }
            .kpi .v { margin-top: 6px; font-size: 20px; font-weight: 800; }
            .kpi .v.forest { color: #2e7d32; }
            .table-wrap { margin-top: 28px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
            .table-head {
                display: flex; justify-content: space-between; align-items: center;
                padding: 14px 16px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;
            }
            .table-head h2 { font-size: 14px; font-weight: 700; }
            table { width: 100%; border-collapse: collapse; font-size: 13px; }
            th {
                text-align: left; padding: 10px 16px; color: #64748b;
                font-size: 11px; letter-spacing: 1px; text-transform: uppercase;
                border-bottom: 1px solid #f1f5f9;
            }
            td { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; }
            tr:last-child td { border-bottom: none; }
            td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
            td .muted { color: #64748b; font-size: 12px; }
            .actions { max-width: 860px; margin: 20px auto 0; text-align: right; }
            .actions button, .actions a {
                display: inline-block; margin-left: 8px; border: none; cursor: pointer;
                font-weight: 600; padding: 10px 20px; border-radius: 8px; font-size: 14px;
                text-decoration: none;
            }
            .actions button { background: #2e7d32; color: #fff; }
            .actions a { background: #f1f5f9; color: #0f172a; }
            .footer {
                margin-top: 24px; padding: 18px 36px; border-top: 1px dashed #cbd5e1;
                display: flex; justify-content: space-between; font-size: 12px; color: #64748b;
            }
            .footer .stamp {
                border: 2px solid #2e7d32; color: #2e7d32; border-radius: 8px; padding: 6px 12px;
                font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
            }
            @media print {
                body { background: #fff; padding: 0; }
                .actions { display: none; }
                .sheet { border: none; border-radius: 0; }
            }
            @media (max-width: 640px) { .grid { grid-template-columns: repeat(2, 1fr); } }
        </style>
    </head>
    <body>
        <div class="actions">
            <a href="{{ route('admin.employers.show', $employer) }}">← Back to employer</a>
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
                <div class="label">
                    Corporate mobility report<br>
                    <span style="color:#fff;letter-spacing:0;text-transform:none;font-size:12px">{{ $month->format('F Y') }}</span>
                </div>
            </div>

            <div class="body">
                <h1 class="title">CO₂ &amp; Subsidy Report</h1>
                <p class="sub"><strong>{{ $employer->name }}</strong> · {{ $employer->program_type->label() }} program · issued {{ now()->format('d M Y, H:i') }}</p>

                <div class="grid">
                    <div class="kpi">
                        <p class="k">Staff covered</p>
                        <p class="v">{{ number_format($staff_covered) }}</p>
                    </div>
                    <div class="kpi">
                        <p class="k">Rides covered</p>
                        <p class="v">{{ number_format($rides_covered) }}</p>
                    </div>
                    <div class="kpi">
                        <p class="k">Coverage spent</p>
                        <p class="v">₦{{ number_format($coverage_spent, 2) }}</p>
                    </div>
                    <div class="kpi">
                        <p class="k">CO₂ saved</p>
                        <p class="v forest">{{ number_format($co2_kg, 2) }} kg</p>
                    </div>
                    <div class="kpi">
                        <p class="k">Fuel saved</p>
                        <p class="v">{{ number_format($fuel_litres, 2) }} L</p>
                    </div>
                    <div class="kpi">
                        <p class="k">Trees equivalent</p>
                        <p class="v">{{ number_format($trees, 2) }}</p>
                    </div>
                    <div class="kpi">
                        <p class="k">Avg coverage / ride</p>
                        <p class="v">₦{{ number_format($rides_covered ? $coverage_spent / $rides_covered : 0, 2) }}</p>
                    </div>
                    <div class="kpi">
                        <p class="k">CO₂ / ride</p>
                        <p class="v">{{ number_format($rides_covered ? $co2_kg / $rides_covered : 0, 2) }} kg</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <div class="table-head"><h2>Per-employee impact</h2></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th class="num">Rides</th>
                                <th class="num">Coverage (₦)</th>
                                <th class="num">CO₂ (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($per_employee as $row)
                                <tr>
                                    <td>
                                        {{ $row['name'] }}
                                        <div class="muted">{{ $row['email'] }}</div>
                                    </td>
                                    <td class="num">{{ number_format($row['rides']) }}</td>
                                    <td class="num">{{ number_format($row['coverage'], 2) }}</td>
                                    <td class="num">{{ number_format($row['co2_kg'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;color:#64748b;padding:24px">No covered rides in {{ $month->format('F Y') }}.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($per_day->isNotEmpty())
                    <div class="table-wrap">
                        <div class="table-head"><h2>Daily ride volume</h2></div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="num">Rides</th>
                                    <th class="num">Coverage (₦)</th>
                                    <th class="num">CO₂ (kg)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($per_day as $row)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td>
                                        <td class="num">{{ number_format($row['rides']) }}</td>
                                        <td class="num">{{ number_format($row['coverage'], 2) }}</td>
                                        <td class="num">{{ number_format($row['co2_kg'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="footer">
                <span>Prepared by WorkRide Ops Control Tower · data from covered bookings (non-cancelled)</span>
                <span class="stamp">WorkRide Verified</span>
            </div>
        </div>
    </body>
</html>
