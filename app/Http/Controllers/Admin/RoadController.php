<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoadEvent;
use App\Models\RoadSegment;
use App\Services\RoadIntelligenceService;

/**
 * Road Intelligence dashboard for the Ops Control Tower — confirmed potholes,
 * IRI segments, and a FERMA-ready CSV export (selling the Road API).
 */
class RoadController extends Controller
{
    public function index(RoadIntelligenceService $intelligence)
    {
        $stats = [
            'total_events' => RoadEvent::count(),
            'confirmed_potholes' => RoadEvent::where('type', 'pothole')->where('is_confirmed', true)->count(),
            'unconfirmed' => RoadEvent::where('is_confirmed', false)->count(),
            'segments' => RoadSegment::count(),
        ];

        $recentEvents = RoadEvent::with('user')
            ->latest()
            ->limit(10)
            ->get();

        $segmentsByCondition = $intelligence->segmentsByCondition();

        return view('admin.road', [
            'stats' => $stats,
            'recentEvents' => $recentEvents,
            'segmentsByCondition' => $segmentsByCondition,
        ]);
    }

    public function export(RoadIntelligenceService $intelligence)
    {
        $rows = $intelligence->fermaExport();
        $headers = ['road_name', 'lat', 'lng', 'type', 'severity', 'reported_at'];

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);

        foreach ($rows as $row) {
            fputcsv($csv, array_values($row));
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="workride-confirmed-potholes-'.now()->format('Ymd-His').'.csv"',
        ]);
    }
}
