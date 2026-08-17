<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SystemSettingController extends Controller
{
    public function getLandingHero(): JsonResponse
    {
        $setting = SystemSetting::where('key', 'landing_hero')->first();
        $data = $setting ? $setting->value : [];

        $imagePath = $data['image_path'] ?? null;
        $imageUrl = null;

        if ($imagePath) {
            $imageUrl = filter_var($imagePath, FILTER_VALIDATE_URL)
                ? $imagePath
                : url('storage/' . $imagePath);
        }

        return response()->json([
            'data' => [
                'hero_image' => $imageUrl,
                'image_path' => $imagePath,
                'title' => $data['title'] ?? "Welcome to\nYouth Community",
                'subtitle' => $data['subtitle'] ?? "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy.",
                'show_text' => isset($data['show_text']) ? (bool)$data['show_text'] : true,
                'show_button' => isset($data['show_button']) ? (bool)$data['show_button'] : true,
                'button_text' => $data['button_text'] ?? 'Register',
                'button_link' => $data['button_link'] ?? '/register',
                'gradient_start' => $data['gradient_start'] ?? 'rgba(5, 110, 61, 0.95)',
                'gradient_end' => $data['gradient_end'] ?? 'rgba(14, 136, 70, 0.35)',
                'gradient_angle' => $data['gradient_angle'] ?? '90deg',
                'title_color' => $data['title_color'] ?? '#ffffff',
                'subtitle_color' => $data['subtitle_color'] ?? '#ffffff',
                'button_bg_color' => $data['button_bg_color'] ?? '#ffffff',
                'button_text_color' => $data['button_text_color'] ?? '#06763d',
            ],
        ]);
    }

    public function updateLandingHero(Request $request): JsonResponse
    {
        $request->validate([
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:2000',
            'show_text' => 'nullable',
            'show_button' => 'nullable',
            'button_text' => 'nullable|string|max:50',
            'button_link' => 'nullable|string|max:255',
            'gradient_start' => 'nullable|string|max:100',
            'gradient_end' => 'nullable|string|max:100',
            'gradient_angle' => 'nullable|string|max:50',
            'title_color' => 'nullable|string|max:50',
            'subtitle_color' => 'nullable|string|max:50',
            'button_bg_color' => 'nullable|string|max:50',
            'button_text_color' => 'nullable|string|max:50',
            'remove_image' => 'nullable',
        ]);

        $setting = SystemSetting::firstOrCreate(['key' => 'landing_hero'], ['value' => []]);
        $currentValue = is_array($setting->value) ? $setting->value : [];
        $imagePath = $currentValue['image_path'] ?? null;

        if (filter_var($request->input('remove_image'), FILTER_VALIDATE_BOOLEAN)) {
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = null;
        }

        if ($request->hasFile('hero_image')) {
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('hero_image')->store('hero', 'public');
        }

        $showText = $request->has('show_text')
            ? filter_var($request->input('show_text'), FILTER_VALIDATE_BOOLEAN)
            : ($currentValue['show_text'] ?? true);

        $showButton = $request->has('show_button')
            ? filter_var($request->input('show_button'), FILTER_VALIDATE_BOOLEAN)
            : ($currentValue['show_button'] ?? true);

        $updatedData = [
            'image_path' => $imagePath,
            'title' => $request->input('title', $currentValue['title'] ?? "Welcome to\nYouth Community"),
            'subtitle' => $request->input('subtitle', $currentValue['subtitle'] ?? "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy."),
            'show_text' => $showText,
            'show_button' => $showButton,
            'button_text' => $request->input('button_text', $currentValue['button_text'] ?? 'Register'),
            'button_link' => $request->input('button_link', $currentValue['button_link'] ?? '/register'),
            'gradient_start' => $request->input('gradient_start', $currentValue['gradient_start'] ?? 'rgba(5, 110, 61, 0.95)'),
            'gradient_end' => $request->input('gradient_end', $currentValue['gradient_end'] ?? 'rgba(14, 136, 70, 0.35)'),
            'gradient_angle' => $request->input('gradient_angle', $currentValue['gradient_angle'] ?? '90deg'),
            'title_color' => $request->input('title_color', $currentValue['title_color'] ?? '#ffffff'),
            'subtitle_color' => $request->input('subtitle_color', $currentValue['subtitle_color'] ?? '#ffffff'),
            'button_bg_color' => $request->input('button_bg_color', $currentValue['button_bg_color'] ?? '#ffffff'),
            'button_text_color' => $request->input('button_text_color', $currentValue['button_text_color'] ?? '#06763d'),
        ];

        $setting->update(['value' => $updatedData]);

        $imageUrl = null;
        if ($imagePath) {
            $imageUrl = url('storage/' . $imagePath);
        }

        return response()->json([
            'message' => 'Landing page hero settings updated successfully.',
            'data' => array_merge($updatedData, [
                'hero_image' => $imageUrl,
            ]),
        ]);
    }

    public function getAuthHero(): JsonResponse
    {
        $setting = SystemSetting::where('key', 'auth_hero')->first();
        $data = $setting ? $setting->value : [];

        $imagePath = $data['image_path'] ?? null;
        $imageUrl = null;

        if ($imagePath) {
            $imageUrl = filter_var($imagePath, FILTER_VALIDATE_URL)
                ? $imagePath
                : url('storage/' . $imagePath);
        }

        return response()->json([
            'data' => [
                'auth_image' => $imageUrl,
                'image_path' => $imagePath,
                'title' => $data['title'] ?? "Join Youth Community\nand be part of a better tomorrow",
                'subtitle' => $data['subtitle'] ?? "Create your profile so you can access youth programs, opportunities, and community announcements in one place.",
                'show_text' => isset($data['show_text']) ? (bool)$data['show_text'] : true,
                'gradient_start' => $data['gradient_start'] ?? 'rgba(5, 110, 61, 0.96)',
                'gradient_end' => $data['gradient_end'] ?? 'rgba(14, 136, 70, 0.28)',
                'gradient_angle' => $data['gradient_angle'] ?? '180deg',
                'title_color' => $data['title_color'] ?? '#ffffff',
                'subtitle_color' => $data['subtitle_color'] ?? 'rgba(255, 255, 255, 0.90)',
            ],
        ]);
    }

    public function updateAuthHero(Request $request): JsonResponse
    {
        $request->validate([
            'auth_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:2000',
            'show_text' => 'nullable',
            'gradient_start' => 'nullable|string|max:100',
            'gradient_end' => 'nullable|string|max:100',
            'gradient_angle' => 'nullable|string|max:50',
            'title_color' => 'nullable|string|max:50',
            'subtitle_color' => 'nullable|string|max:50',
            'remove_image' => 'nullable',
        ]);

        $setting = SystemSetting::firstOrCreate(['key' => 'auth_hero'], ['value' => []]);
        $currentValue = is_array($setting->value) ? $setting->value : [];
        $imagePath = $currentValue['image_path'] ?? null;

        if (filter_var($request->input('remove_image'), FILTER_VALIDATE_BOOLEAN)) {
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = null;
        }

        if ($request->hasFile('auth_image')) {
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('auth_image')->store('auth_hero', 'public');
        }

        $showText = $request->has('show_text')
            ? filter_var($request->input('show_text'), FILTER_VALIDATE_BOOLEAN)
            : ($currentValue['show_text'] ?? true);

        $updatedData = [
            'image_path' => $imagePath,
            'title' => $request->input('title', $currentValue['title'] ?? "Join Youth Community\nand be part of a better tomorrow"),
            'subtitle' => $request->input('subtitle', $currentValue['subtitle'] ?? "Create your profile so you can access youth programs, opportunities, and community announcements in one place."),
            'show_text' => $showText,
            'gradient_start' => $request->input('gradient_start', $currentValue['gradient_start'] ?? 'rgba(5, 110, 61, 0.96)'),
            'gradient_end' => $request->input('gradient_end', $currentValue['gradient_end'] ?? 'rgba(14, 136, 70, 0.28)'),
            'gradient_angle' => $request->input('gradient_angle', $currentValue['gradient_angle'] ?? '180deg'),
            'title_color' => $request->input('title_color', $currentValue['title_color'] ?? '#ffffff'),
            'subtitle_color' => $request->input('subtitle_color', $currentValue['subtitle_color'] ?? 'rgba(255, 255, 255, 0.90)'),
        ];

        $setting->update(['value' => $updatedData]);

        $imageUrl = null;
        if ($imagePath) {
            $imageUrl = url('storage/' . $imagePath);
        }

        return response()->json([
            'message' => 'Login/Register auth hero settings updated successfully.',
            'data' => array_merge($updatedData, [
                'auth_image' => $imageUrl,
            ]),
        ]);
    }

    public function getContactSettings(): JsonResponse
    {
        $setting = SystemSetting::where('key', 'contact_info')->first();
        $data = $setting ? $setting->value : [];

        return response()->json([
            'data' => [
                'office_name' => $data['office_name'] ?? 'Tagum City Youth Development Office',
                'email' => $data['email'] ?? 'youth@tagum.gov.ph',
                'phone' => $data['phone'] ?? '(084) 123-4567',
                'address' => $data['address'] ?? 'Tagum City Hall, Tagum City, Davao del Norte',
                'social_links' => $data['social_links'] ?? [
                    ['platform' => 'facebook', 'url' => 'https://facebook.com'],
                    ['platform' => 'instagram', 'url' => 'https://instagram.com'],
                ],
            ],
        ]);
    }

    public function updateContactSettings(Request $request): JsonResponse
    {
        $request->validate([
            'office_name' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'social_links' => 'nullable|array',
            'social_links.*.platform' => 'nullable|string|max:50',
            'social_links.*.url' => 'nullable|string|max:500',
        ]);

        $setting = SystemSetting::firstOrCreate(['key' => 'contact_info'], ['value' => []]);
        $currentValue = is_array($setting->value) ? $setting->value : [];

        // Filter and clean social links
        $rawSocialLinks = $request->input('social_links', []);
        $cleanedSocialLinks = [];
        if (is_array($rawSocialLinks)) {
            foreach ($rawSocialLinks as $item) {
                if (!empty($item['url'])) {
                    $cleanedSocialLinks[] = [
                        'platform' => $item['platform'] ?? 'website',
                        'url' => trim($item['url']),
                    ];
                }
            }
        }

        $updatedData = [
            'office_name' => $request->input('office_name', $currentValue['office_name'] ?? 'Tagum City Youth Development Office'),
            'email' => $request->input('email', $currentValue['email'] ?? 'youth@tagum.gov.ph'),
            'phone' => $request->input('phone', $currentValue['phone'] ?? '(084) 123-4567'),
            'address' => $request->input('address', $currentValue['address'] ?? 'Tagum City Hall, Tagum City, Davao del Norte'),
            'social_links' => $cleanedSocialLinks,
        ];

        $setting->update(['value' => $updatedData]);

        return response()->json([
            'message' => 'Contact & social links updated successfully.',
            'data' => $updatedData,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password does not match your actual password.'],
            ]);
        }

        if (Hash::check($request->new_password, $user->password)) {
            throw ValidationException::withMessages([
                'new_password' => ['The new password cannot be the same as your current password.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}