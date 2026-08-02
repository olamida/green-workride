<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\Vehicle;
use App\Services\EmployerService;
use App\Services\VehicleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Rider-side employer mobility — Form 1 (self-request to join) and the
 * self-service vehicle page. Employer confirmation (approval) grants Level 1
 * workplace verification automatically, so approved staff book immediately.
 */
class EmployerRequestController extends Controller
{
    public function employers(Request $request)
    {
        $user = $request->user()->load('employerMemberships.employer');

        $openEmployers = Employer::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('employers.join', compact('user', 'openEmployers'));
    }

    public function join(Request $request, Employer $employer, EmployerService $service)
    {
        try {
            $member = $service->requestJoin($employer, $request->user());

            $message = $member->isPending()
                ? 'Request sent — your employer will approve it shortly.'
                : 'You are already a member of this employer program.';
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return back()->with('status', $message);
    }

    public function vehicles(Request $request)
    {
        $vehicles = $request->user()->vehicles()->latest()->get();

        return view('employers.vehicles', compact('vehicles'));
    }

    public function storeVehicle(Request $request, VehicleService $service)
    {
        $data = $request->validate([
            'plate_number' => ['required', 'string', 'max:30', Rule::unique('vehicles', 'plate_number')],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'seats' => ['required', 'integer', 'min:1', 'max:100'],
            'type' => ['required', Rule::in(['sedan', 'coaster', 'staff_bus', 'danfo'])],
        ]);

        $service->store($request->user(), $data);

        return back()->with('status', 'Vehicle registered. A Control Tower admin will verify its papers.');
    }

    public function destroyVehicle(Request $request, Vehicle $vehicle, VehicleService $service)
    {
        $service->assertNotOwned($vehicle, $request->user());

        $vehicle->delete();

        return back()->with('status', 'Vehicle removed.');
    }
}
