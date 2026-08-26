<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EcesproSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcesproSettingController extends Controller
{
    /**
     * Get all ECESPRO settings.
     */
    public function index(): JsonResponse
    {
        $settings = EcesproSetting::all()->pluck('value', 'key');
        $requiredVolunteerHours = (float) ($settings->get('required_volunteer_hours') ?? 36.00);

        return response()->json([
            'data' => [
                'benefits' => $settings->get('benefits', []),
                'eligibility' => $settings->get('eligibility', []),
                'required_volunteer_hours' => $requiredVolunteerHours,
            ],
        ]);
    }

    /**
     * Store or update an ECESPRO setting.
     */
    public function store(Request $request, ?string $key = null): JsonResponse
    {
        $targetKey = $key ?? $request->input('key');

        // Handle single setting key update
        if ($targetKey === 'required_volunteer_hours') {
            $validated = $request->validate([
                'value' => 'required|numeric|min:1|max:500',
            ]);

            $setting = EcesproSetting::set('required_volunteer_hours', (float) $validated['value']);

            return response()->json([
                'message' => 'Required volunteer hours setting updated successfully.',
                'data' => [
                    'key' => 'required_volunteer_hours',
                    'value' => (float) $setting->value,
                ],
            ]);
        }

        if (in_array($targetKey, ['benefits', 'eligibility'], true)) {
            $validated = $request->validate([
                'value' => 'required|array',
                'value.*' => 'string',
            ]);

            $setting = EcesproSetting::set($targetKey, $validated['value']);

            return response()->json([
                'message' => ucfirst($targetKey).' setting updated successfully.',
                'data' => $setting,
            ]);
        }

        // Handle full payload / batch update (e.g. POST /api/admin/ecespro-settings)
        $validated = $request->validate([
            'required_volunteer_hours' => 'nullable|numeric|min:1|max:500',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string',
            'eligibility' => 'nullable|array',
            'eligibility.*' => 'string',
        ]);

        if (isset($validated['required_volunteer_hours'])) {
            EcesproSetting::set('required_volunteer_hours', (float) $validated['required_volunteer_hours']);
        }
        if (isset($validated['benefits'])) {
            EcesproSetting::set('benefits', $validated['benefits']);
        }
        if (isset($validated['eligibility'])) {
            EcesproSetting::set('eligibility', $validated['eligibility']);
        }

        return $this->index();
    }
}
