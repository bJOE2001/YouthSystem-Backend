<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NoBudgetReason;
use Illuminate\Http\Request;

class NoBudgetReasonController extends Controller
{
    public function index(Request $request)
    {
        $query = NoBudgetReason::query();
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        if ($request->boolean('all')) {
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            return response()->json($query->get());
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
        return response()->json(NoBudgetReason::create($validated), 201);
    }

    public function update(Request $request, NoBudgetReason $noBudgetReason)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string|in:active,inactive',
        ]);
        $noBudgetReason->update($validated);
        return response()->json($noBudgetReason);
    }

    public function destroy(NoBudgetReason $noBudgetReason)
    {
        $noBudgetReason->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
