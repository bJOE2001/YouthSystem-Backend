<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StrategicDirection;
use Illuminate\Http\Request;

class StrategicDirectionController extends Controller
{
    public function index(Request $request)
    {
        $query = StrategicDirection::query();
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        if ($request->has('all') && $request->all == 'true') {
            return response()->json($query->where('status', 'active')->get());
        }

        $perPage = $request->input('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string|in:active,inactive',
        ]);
        return response()->json(StrategicDirection::create($validated), 201);
    }

    public function update(Request $request, StrategicDirection $strategicDirection)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string|in:active,inactive',
        ]);
        $strategicDirection->update($validated);
        return response()->json($strategicDirection);
    }

    public function destroy(StrategicDirection $strategicDirection)
    {
        $strategicDirection->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
