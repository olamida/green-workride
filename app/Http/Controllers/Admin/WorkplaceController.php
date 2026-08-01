<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workplace;
use Illuminate\Http\Request;

class WorkplaceController extends Controller
{
    public function index(Request $request)
    {
        $workplaces = Workplace::withCount('users')
            ->when($request->query('zone'), function ($query, $zone) {
                $query->where('zone', $zone);
            })
            ->orderBy('name')
            ->get();

        return view('admin.workplaces', compact('workplaces'));
    }
}
