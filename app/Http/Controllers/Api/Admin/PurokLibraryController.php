<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurokResource;
use App\Models\Barangay;
use App\Models\Purok;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PurokLibraryController extends Controller
{
    /**
     * Display a listing of puroks, with optional barangay and search filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Purok::query();

        if ($request->filled('barangay')) {
            $barangay = trim($request->barangay);
            if (is_numeric($barangay)) {
                $query->where('barangay_id', (int) $barangay);
            } else {
                $query->whereRaw('LOWER(barangay) = ?', [strtolower($barangay)]);
            }
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', $search)
                    ->orWhere('barangay', 'LIKE', $search);
            });
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['id', 'name', 'barangay', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, strtolower($sortOrder) === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('barangay', 'asc')->orderBy('name', 'asc');
        }

        if ($request->boolean('all')) {
            return PurokResource::collection($query->get())->response();
        }

        $perPage = (int) $request->input('per_page', 15);
        $puroks = $query->paginate($perPage);

        return PurokResource::collection($puroks)->response();
    }

    /**
     * Store one or a list of puroks for a specified barangay.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barangay' => ['required', 'string', 'max:255'],
            'name' => ['required_without:names', 'nullable', 'string'],
            'names' => ['nullable', 'array'],
            'names.*' => ['string', 'max:100'],
        ]);

        $barangayName = trim($validated['barangay']);
        $barangayModel = Barangay::whereRaw('LOWER(name) = ?', [strtolower($barangayName)])->first();
        $barangayId = $barangayModel?->id;
        $userId = $request->user()?->id ?? Auth::id();

        // Extract list of names from 'names' array or 'name' (split by commas/newlines)
        $names = [];
        if (! empty($validated['names']) && is_array($validated['names'])) {
            $names = $validated['names'];
        } elseif (! empty($validated['name'])) {
            $rawList = preg_split('/[\r\n,]+/', $validated['name']);
            $names = array_values(array_filter(array_map('trim', $rawList)));
        }

        if (empty($names)) {
            return response()->json([
                'message' => 'Please provide at least one purok name.',
                'errors' => ['name' => ['Purok name is required.']],
            ], 422);
        }

        // If single name provided and it's duplicate
        if (count($names) === 1) {
            $singleName = $names[0];
            $exists = Purok::where('barangay', $barangayName)
                ->whereRaw('LOWER(name) = ?', [strtolower($singleName)])
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => "Purok '{$singleName}' already exists in Barangay {$barangayName}.",
                    'errors' => ['name' => ["Purok '{$singleName}' already exists in Barangay {$barangayName}."]],
                ], 422);
            }

            $purok = Purok::create([
                'barangay' => $barangayModel?->name ?? $barangayName,
                'barangay_id' => $barangayId,
                'name' => $singleName,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Purok created successfully.',
                'data' => PurokResource::make($purok),
            ], 201);
        }

        // Multiple names provided (bulk add list of puroks)
        $createdList = [];
        $skippedCount = 0;

        foreach ($names as $nameItem) {
            $trimmed = trim($nameItem);
            if (! $trimmed) continue;

            $existing = Purok::where('barangay', $barangayName)
                ->whereRaw('LOWER(name) = ?', [strtolower($trimmed)])
                ->first();

            if ($existing) {
                $skippedCount++;
                continue;
            }

            $created = Purok::create([
                'barangay' => $barangayModel?->name ?? $barangayName,
                'barangay_id' => $barangayId,
                'name' => $trimmed,
                'user_id' => $userId,
            ]);

            $createdList[] = $created;
        }

        $message = count($createdList) . ' purok(s) added successfully.';
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} duplicate(s) were skipped).";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'count' => count($createdList),
            'data' => count($createdList) === 1 ? PurokResource::make($createdList[0]) : PurokResource::collection(collect($createdList)),
        ], 201);
    }

    /**
     * Display the specified purok.
     */
    public function show(Purok $purok): JsonResponse
    {
        return response()->json(PurokResource::make($purok));
    }

    /**
     * Update the specified purok.
     */
    public function update(Request $request, Purok $purok): JsonResponse
    {
        $targetBarangay = $request->input('barangay', $purok->barangay);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('puroks', 'name')
                    ->where('barangay', $targetBarangay)
                    ->ignore($purok->id),
            ],
            'barangay' => ['sometimes', 'string', 'max:255'],
        ]);

        $updateData = ['name' => trim($validated['name'])];

        if ($request->filled('barangay')) {
            $barangayName = trim($request->barangay);
            $barangayModel = Barangay::whereRaw('LOWER(name) = ?', [strtolower($barangayName)])->first();
            $updateData['barangay'] = $barangayModel?->name ?? $barangayName;
            $updateData['barangay_id'] = $barangayModel?->id;
        }

        $purok->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Purok updated successfully.',
            'data' => PurokResource::make($purok),
        ]);
    }

    /**
     * Remove the specified purok from storage.
     */
    public function destroy(Purok $purok): JsonResponse
    {
        $purok->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purok deleted successfully.',
        ]);
    }
}
