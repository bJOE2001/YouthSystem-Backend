<?php

namespace App\Http\Controllers\Auth;

use App\Actions\YouthProfile\CreateYouthProfileAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\SkOfficial;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $credentials['status'] = UserStatus::Active->value;

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $user = $request->user();

        // Single Session Enforcement: Revoke previous tokens & sessions
        $user->tokens()->delete();
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => UserResource::make($user),
            'token' => $token,
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function register(
        RegisterRequest $request,
        CreateYouthProfileAction $createYouthProfile
    ): JsonResponse {
        $data = $request->validated();

        $name = trim(implode(' ', array_filter([
            $data['firstName'],
            $data['middleName'] ?? null,
            $data['lastName'],
            $data['suffix'] ?? null,
        ])));

        $user = User::create([
            'name' => $name,
            'email' => $data['email'],
            'password' => Hash::make(Str::random(32)),
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Pending->value,
        ]);

        $mappedProfileData = [
            'first_name' => $data['firstName'],
            'middle_name' => $data['middleName'] ?? null,
            'last_name' => $data['lastName'],
            'suffix' => $data['suffix'] ?? null,
            'gender' => $data['gender'],
            'birth_date' => $data['birthdate'],
            'place_of_birth' => $data['placeOfBirth'],
            'mobile_number' => $data['mobileNumber'],
            'father_first_name' => $data['fatherFirstName'] ?? null,
            'father_middle_name' => $data['fatherMiddleName'] ?? null,
            'father_last_name' => $data['fatherLastName'] ?? null,
            'mother_first_name' => $data['motherFirstName'] ?? null,
            'mother_middle_name' => $data['motherMiddleName'] ?? null,
            'mother_last_name' => $data['motherLastName'] ?? null,
            'parents_contact_number' => $data['parentsContactNumber'] ?? null,
            'guardian_first_name' => $data['guardianFirstName'] ?? null,
            'guardian_last_name' => $data['guardianLastName'] ?? null,
            'guardian_contact_number' => $data['guardianContactNumber'] ?? null,
            'currently_attending_school' => $data['currentlyAttendingSchool'] === 'yes',
            'senior_high_graduate' => $data['seniorHighGraduate'] === 'yes',
            'educational_attainment' => $data['educationalAttainment'],
            'course_strand' => $data['courseStrand'] ?? null,
            'ethnicity' => $data['ethnicity'] ?? null,
            'religious_affiliation' => $data['religiousAffiliation'] ?? null,
            'has_disability' => $data['hasDisability'] === 'yes',
            'overseas_worker' => $data['overseasWorker'] === 'yes',
            'lgbtq_member' => $data['lgbtqMember'] === 'yes',
            'special_youth_sector' => $data['specialYouthSector'] ?? null,
            'birth_registered' => $data['birthRegistered'] === 'yes',
            'civil_status' => $data['civilStatus'],
            'solo_parent' => $data['soloParent'] === 'yes',
            'barangay' => $data['barangay'],
            'purok_sitio' => $data['purokSitio'],
            'city' => $data['city'],
            'province' => $data['province'],
            'postal_code' => $data['zipcode'],
        ];

        $createYouthProfile->execute(
            $user,
            $mappedProfileData,
            $request->file('attachedId'),
        );

        return response()->json([
            'message' => 'Registration successful',
            'user' => UserResource::make($user),
        ], Response::HTTP_CREATED);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => UserResource::make($request->user()),
        ]);
    }

    public function qrPass(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->ensureQrToken();
        $user->loadMissing(['youthProfile', 'scholar', 'skOfficial']);

        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        $isScholar = $user->scholar && $user->scholar->status === 'Active';

        $position = null;
        $committee = null;
        $term = null;
        $barangay = $user->youthProfile?->barangay;

        if ($role === 'sk_admin') {
            $skOfficial = $user->skOfficial ?? SkOfficial::where('email', $user->email)->first();
            $position = $skOfficial?->position ?? 'SK Official';
            $committee = $skOfficial?->committee;
            $term = $skOfficial?->term ?? '2023 - 2025';
            $barangay = $skOfficial?->barangay ?? $barangay;
            $roleLabel = $position;
        } elseif ($role === 'admin') {
            $roleLabel = 'TCYDO Administrator';
            $position = 'Administrator';
            $barangay = $barangay ?? 'City Youth Development Office';
        } else {
            $roleLabel = 'Registered Youth';
        }

        $profilePicture = $user->youthProfile?->profile_picture;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'role_label' => $roleLabel,
                'is_scholar' => (bool) $isScholar,
                'scholar_no' => $isScholar ? $user->scholar?->scholar_no : null,
                'position' => $position,
                'committee' => $committee,
                'term' => $term,
                'barangay' => $barangay,
                'profile_picture' => $profilePicture,
                'qr_code_token' => $user->qr_code_token,
                'status' => $user->status instanceof UserStatus ? $user->status->value : (string) $user->status,
            ],
        ]);
    }

    public function regenerateQrPass(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->regenerateQrToken();

        return $this->qrPass($request);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
