<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EcesproSetting;
use Illuminate\Http\Request;

class EcesproSettingController extends Controller
{
    public function index()
    {
        $settings = EcesproSetting::all()->pluck('value', 'key');

        return response()->json([
            'data' => [
                'benefits' => $settings->get('benefits', []),
                'eligibility' => $settings->get('eligibility', []),
            ],
        ]);
    }

    public function store(Request $request, $key)
    {
        $validated = $request->validate([
            'value' => 'required|array',
            'value.*' => 'string',
        ]);

        $setting = EcesproSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $validated['value']]
        );

        return response()->json(['data' => $setting]);
    }
}
