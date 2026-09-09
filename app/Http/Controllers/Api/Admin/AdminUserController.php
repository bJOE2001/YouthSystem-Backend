<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AdminUserController extends Controller
{
    public const AVAILABLE_MODULES = [
        ['key' => 'events', 'label' => 'PPA Management', 'icon' => 'event', 'group' => 'modules'],
        ['key' => 'sports_programs', 'label' => 'Sports Programs', 'icon' => 'sports_soccer', 'group' => 'modules'],
        ['key' => 'facilities', 'label' => 'Sports Facility & Bookings', 'icon' => 'stadium', 'group' => 'modules'],
        ['key' => 'ecespro', 'label' => 'ECESPRO Scholarship', 'icon' => 'school', 'group' => 'modules'],
        ['key' => 'youth_records', 'label' => 'Resident Youth Records', 'icon' => 'folder_shared', 'group' => 'modules'],
        ['key' => 'sk_officials', 'label' => 'SK Officials', 'icon' => 'badge', 'group' => 'modules'],
        ['key' => 'announcements', 'label' => 'Updates / Announcements', 'icon' => 'campaign', 'group' => 'modules'],
        ['key' => 'feedbacks', 'label' => 'User Feedbacks', 'icon' => 'rate_review', 'group' => 'modules'],
        ['key' => 'scanner', 'label' => 'QR Scanner', 'icon' => 'qr_code_scanner', 'group' => 'modules'],
        // System Settings Permissions
        ['key' => 'settings_hero', 'label' => 'Landing Page Hero', 'icon' => 'view_carousel', 'group' => 'settings'],
        ['key' => 'settings_auth_hero', 'label' => 'Login & Register Hero', 'icon' => 'login', 'group' => 'settings'],
        ['key' => 'settings_contact', 'label' => 'Contact & Socials', 'icon' => 'contact_phone', 'group' => 'settings'],
        ['key' => 'settings_email', 'label' => 'Email Layouts', 'icon' => 'mail', 'group' => 'settings'],
        ['key' => 'settings_ecespro', 'label' => 'ECESPRO Settings', 'icon' => 'school', 'group' => 'settings'],
    ];

    /**
     * List all administrator and sub-administrator accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::whereIn('role', [UserRole::Admin, UserRole::SubAdmin])
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->latest('id')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'permissions' => $user->permissions ?? [],
                'is_root' => $user->isRootAdmin(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'users' => $users,
            'data' => $users,
            'available_modules' => self::AVAILABLE_MODULES,
        ]);
    }

    /**
     * Create a new sub-administrator account.
     */
    public function store(Request $request): JsonResponse
    {
        $allowedKeys = array_column(self::AVAILABLE_MODULES, 'key');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in($allowedKeys)],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $subAdmin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::SubAdmin->value,
            'status' => $validated['status'] ?? UserStatus::Active->value,
            'permissions' => array_values(array_unique($validated['permissions'])),
        ]);

        return response()->json([
            'message' => 'Sub-Admin created successfully.',
            'user' => [
                'id' => $subAdmin->id,
                'name' => $subAdmin->name,
                'email' => $subAdmin->email,
                'role' => $subAdmin->role->value,
                'status' => $subAdmin->status->value,
                'permissions' => $subAdmin->permissions ?? [],
                'is_root' => false,
                'last_login_at' => null,
                'created_at' => $subAdmin->created_at?->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing sub-administrator account.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        abort_if(
            $user->isRootAdmin(),
            Response::HTTP_FORBIDDEN,
            'The root administrator account cannot be modified via Sub-Admin Management.'
        );

        $allowedKeys = array_column(self::AVAILABLE_MODULES, 'key');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in($allowedKeys)],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        $user->permissions = array_values(array_unique($validated['permissions']));

        if (! empty($validated['status'])) {
            $user->status = UserStatus::from($validated['status']);
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'Sub-Admin updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'permissions' => $user->permissions ?? [],
                'is_root' => false,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Change the email address of a sub-administrator.
     */
    public function changeEmail(Request $request, User $user): JsonResponse
    {
        abort_if(
            $user->isRootAdmin(),
            Response::HTTP_FORBIDDEN,
            'The root administrator account email cannot be modified via Sub-Admin Management.'
        );

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::notIn([$user->email]),
            ],
        ], [
            'email.not_in' => 'The new email must be different from the current email address.',
        ]);

        $user->email = $validated['email'];
        $user->save();

        return response()->json([
            'message' => 'Sub-administrator email updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'permissions' => $user->permissions ?? [],
                'is_root' => false,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Toggle active/inactive status of a sub-administrator.
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        abort_if(
            $user->isRootAdmin(),
            Response::HTTP_FORBIDDEN,
            'The root administrator account status cannot be modified.'
        );

        $newStatus = $user->isActive() ? UserStatus::Inactive : UserStatus::Active;
        $user->status = $newStatus;
        $user->save();

        // If deactivated, revoke all active sessions immediately
        if ($newStatus === UserStatus::Inactive) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Status updated successfully.',
            'status' => $user->status->value,
        ]);
    }

    /**
     * Delete a sub-administrator account.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_if(
            $user->isRootAdmin(),
            Response::HTTP_FORBIDDEN,
            'The root administrator account cannot be deleted.'
        );

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Sub-Admin account deleted successfully.',
        ]);
    }
}
