<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Actions\SkAdmin\SkOfficials\CreateSkOfficialAction;
use App\Actions\SkAdmin\SkOfficials\DeleteSkOfficialAction;
use App\Actions\SkAdmin\SkOfficials\GetSkOfficialDetailsAction;
use App\Actions\SkAdmin\SkOfficials\GetSkOfficialsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\SkAdmin\SkOfficialResource;
use App\Models\SkOfficial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkOfficialController extends Controller
{
    public function index(Request $request, GetSkOfficialsAction $action): JsonResponse
    {
        $officials = $action->execute($request->all());

        return response()->json(SkOfficialResource::collection($officials));
    }

    public function store(Request $request, CreateSkOfficialAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'initials' => 'nullable|string|max:10',
            'barangay' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'committee' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'responsibilities' => 'nullable|string',
        ]);

        $official = $action->execute($validated);

        return response()->json([
            'success' => true,
            'message' => 'SK Official added successfully.',
            'data' => SkOfficialResource::make($official),
        ], 201);
    }

    public function show(SkOfficial $skOfficial, GetSkOfficialDetailsAction $action): JsonResponse
    {
        $official = $action->execute($skOfficial);

        return response()->json(SkOfficialResource::make($official));
    }

    public function destroy(SkOfficial $skOfficial, DeleteSkOfficialAction $action): JsonResponse
    {
        $action->execute($skOfficial);

        return response()->json([
            'success' => true,
            'message' => 'SK Official removed successfully.',
        ]);
    }
}
