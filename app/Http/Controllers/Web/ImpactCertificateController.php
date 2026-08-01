<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ImpactStat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ImpactCertificateController extends Controller
{
    /**
     * Printable certificate — CO2 Impact or Fuel Saved (CSR) — with a QR that
     * verifies the credential against the public impact page.
     */
    public function show(string $type)
    {
        abort_if(! in_array($type, ['co2', 'fuel'], true), 404);

        $user = auth()->user();
        $user->load('impactStat');

        $stat = $user->impactStat ?: new ImpactStat([
            'total_trips' => 0,
            'co2_saved_kg' => 0,
            'fuel_saved_litres' => 0,
            'trees_equivalent' => 0,
            'level' => 1,
        ]);

        $verifyUrl = route('impact.verify', [$user->id, $type]);
        $rank = $this->rank($user->id, $type);
        $percentile = $this->percentile($user->id, $type);

        // QR image data URI (SVG) for the printable certificate.
        $qr = base64_encode(QrCode::format('svg')->size(120)->generate($verifyUrl));

        return view('impact.certificate', [
            'type' => $type,
            'user' => $user,
            'stat' => $stat,
            'verifyUrl' => $verifyUrl,
            'qrDataUri' => 'data:image/svg+xml;base64,'.$qr,
            'rank' => $rank,
            'percentile' => $percentile,
            'issuedAt' => now(),
        ]);
    }

    /**
     * Public verification page — the QR decodes to this URL.
     */
    public function verify(int $userId, string $type)
    {
        abort_if(! in_array($type, ['co2', 'fuel'], true), 404);

        $user = User::with('impactStat')->findOrFail($userId);
        $stat = $user->impactStat ?: new ImpactStat;

        $verified = $type === 'co2'
            ? (float) $stat->co2_saved_kg > 0
            : (float) $stat->fuel_saved_litres > 0;

        return view('impact.verify', compact('user', 'stat', 'type', 'verified'));
    }

    private function rank(int $userId, string $type): int
    {
        $column = $type === 'co2' ? 'co2_saved_kg' : 'fuel_saved_litres';

        return (int) DB::table('impact_stats')
            ->where($column, '>', DB::table('impact_stats')->where('user_id', $userId)->value($column) ?? 0)
            ->count() + 1;
    }

    private function percentile(int $userId, string $type): int
    {
        $column = $type === 'co2' ? 'co2_saved_kg' : 'fuel_saved_litres';
        $total = (int) DB::table('impact_stats')->count();

        if ($total < 2) {
            return 100;
        }

        $mine = (float) DB::table('impact_stats')->where('user_id', $userId)->value($column) ?? 0;
        $ahead = (int) DB::table('impact_stats')->where($column, '>', $mine)->count();

        return (int) round((1 - $ahead / $total) * 100);
    }
}
