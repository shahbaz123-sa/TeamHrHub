<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use Modules\HRM\Models\Holiday;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\HolidayRequest;
use Modules\HRM\Http\Resources\HolidayResource;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $query = Holiday::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('description', 'ilike', '%' . $request->search . '%');
        }

        // Filter by recurring holidays
        if ($request->has('is_recurring') && $request->is_recurring !== null) {
            $query->where('is_recurring', $request->is_recurring);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('date', '<=', $request->end_date);
        }

        // Order by date
        $query->orderBy('date', 'asc');

        $holidays = $query->paginate($request->get('per_page', 15));

        return HolidayResource::collection($holidays);
    }

    public function store(HolidayRequest $request)
    {
        $holiday = Holiday::create($request->validated());

        return new HolidayResource($holiday);
    }

    public function show(Holiday $holiday)
    {
        return new HolidayResource($holiday);
    }

    public function update(HolidayRequest $request, Holiday $holiday)
    {
        $holiday->update($request->validated());

        return new HolidayResource($holiday);
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return response()->json(['message' => 'Holiday deleted successfully']);
    }
}

