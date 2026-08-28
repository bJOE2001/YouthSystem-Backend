<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurokResource;
use App\Models\Barangay;
use App\Models\Purok;
use App\Models\SkOfficial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PurokLibraryController extends Controller
{
    /**
     * Get the assigned barangay of the authenticated SK official.
     */
    protected function getAssignedBarangay(?Request $request = null): string
    {
        $user = $request?->user() ?? Auth::user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $barangay = SkOfficial::where('email', $user->email)->value('barangay');

        if (! $barangay) {
            abort(403, 'No barangay assigned to this SK Official account.');
        }

        return $barangay;
    }

    /**
     * Display a listing of the puroks for the SK official's barangay.
     */
    public function index(Request $request): JsonResponse
    {
        $barangay = $this->getAssignedBarangay($request);
        $query = Purok::query()->where('barangay', $barangay);

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%'.$request->search.'%');
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['id', 'name', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, strtolower($sortOrder) === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($request->boolean('all')) {
            return PurokResource::collection($query->get())->response();
        }

        $perPage = (int) $request->input('per_page', 15);
        $puroks = $query->paginate($perPage);

        return PurokResource::collection($puroks)->response();
    }

    /**
     * Store a newly created purok in the SK official's barangay.
     */
    public function store(Request $request): JsonResponse
    {
        $barangay = $this->getAssignedBarangay($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('puroks', 'name')->where('barangay', $barangay),
            ],
        ]);

        $barangayModel = Barangay::where('name', $barangay)->first();

        $purok = Purok::create([
            'barangay' => $barangay,
            'barangay_id' => $barangayModel?->id,
            'name' => $validated['name'],
            'user_id' => $request->user()?->id ?? Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purok created successfully.',
            'data' => PurokResource::make($purok),
        ], 201);
    }

    /**
     * Display the specified purok.
     */
    public function show(Request $request, Purok $purok): JsonResponse
    {
        $barangay = $this->getAssignedBarangay($request);

        if ($purok->barangay !== $barangay) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json(PurokResource::make($purok));
    }

    /**
     * Update the specified purok in the SK official's barangay.
     */
    public function update(Request $request, Purok $purok): JsonResponse
    {
        $barangay = $this->getAssignedBarangay($request);

        if ($purok->barangay !== $barangay) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('puroks', 'name')
                    ->where('barangay', $barangay)
                    ->ignore($purok->id),
            ],
        ]);

        $purok->update([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purok updated successfully.',
            'data' => PurokResource::make($purok),
        ]);
    }

    /**
     * Remove the specified purok from storage.
     */
    public function destroy(Request $request, Purok $purok): JsonResponse
    {
        $barangay = $this->getAssignedBarangay($request);

        if ($purok->barangay !== $barangay) {
            abort(403, 'Unauthorized action.');
        }

        $purok->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purok deleted successfully.',
        ]);
    }
}
