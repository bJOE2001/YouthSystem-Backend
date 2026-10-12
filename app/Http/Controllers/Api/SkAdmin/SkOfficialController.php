<?php

namespace App\Http\Controllers\Api\SkAdmin;

use App\Actions\SkAdmin\SkOfficials\CreateSkOfficialAction;
use App\Actions\SkAdmin\SkOfficials\DeleteSkOfficialAction;
use App\Actions\SkAdmin\SkOfficials\GetSkOfficialDetailsAction;
use App\Actions\SkAdmin\SkOfficials\GetSkOfficialsAction;
use App\Enums\YouthProfileStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SkAdmin\SkOfficialResource;
use App\Models\SkOfficial;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkOfficialController extends Controller
{
    public function index(Request $request, GetSkOfficialsAction $action): JsonResponse
    {
        $user = $request->user();
        $params = $request->all();

        // Scope strictly to SK admin's barangay
        $barangay = $user?->getBarangay();
        if (! $barangay) {
            abort(403, 'No barangay assigned to this SK Admin account.');
        }

        $params['barangay'] = $barangay;

        $officials = $action->execute($params);

        return SkOfficialResource::collection($officials)->response();
    }

    public function eligibleYouths(Request $request): JsonResponse
    {
        $user = $request->user();
        $official = $user?->skOfficial ?? ($user?->email ? SkOfficial::where('email', $user->email)->first() : null);
        $barangay = $official?->barangay ?? $user?->youthProfile?->barangay;

        $search = $request->query('search');

        $query = YouthProfile::with('user')
            ->where('status', YouthProfileStatus::Approved)
            ->whereHas('user', function ($q) {
                $q->whereDoesntHave('skOfficial');
            });

        if ($barangay) {
            $query->where('barangay', $barangay);
        }

        if ($search) {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'LIKE', $term)
                    ->orWhere('last_name', 'LIKE', $term)
                    ->orWhere('email', 'LIKE', $term);
            });
        }

        $youths = $query->latest()->limit(50)->get()->map(function ($profile) {
            $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                $profile->first_name,
                $profile->middle_name,
                $profile->last_name,
                $profile->suffix,
            ]))));
            if (empty($name)) {
                $name = $profile->user?->name ?? 'Youth Resident';
            }

            return [
                'user_id' => $profile->user_id,
                'youth_profile_id' => $profile->id,
                'name' => $name,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'email' => $profile->email ?? $profile->user?->email,
                'contact' => $profile->mobile_number,
                'barangay' => $profile->barangay,
                'purok_sitio' => $profile->purok_sitio,
                'profile_picture' => $profile->profile_picture,
                'age' => $profile->birth_date?->age,
                'gender' => $profile->gender,
                'educational_attainment' => $profile->educational_attainment,
                'course_strand' => $profile->course_strand,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $youths,
        ]);
    }

    protected function authorizeBarangayOfficial(User $user, SkOfficial $skOfficial): void
    {
        $skBarangay = $user->getBarangay();
        if (! $skBarangay || strtolower(trim((string) $skOfficial->barangay)) !== strtolower(trim((string) $skBarangay))) {
            abort(403, 'You are not authorized to access SK officials outside your assigned barangay.');
        }
    }

    public function store(Request $request, CreateSkOfficialAction $action): JsonResponse
    {
        $skBarangay = $request->user()?->getBarangay();

        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($skBarangay) {
                    $user = User::with('youthProfile')->find($value);
                    if (! $user || ! $user->youthProfile || $user->youthProfile->status !== YouthProfileStatus::Approved) {
                        $fail('The selected youth must have an approved youth profile.');
                    }
                    if ($skBarangay && $user?->youthProfile?->barangay && strtolower(trim($user->youthProfile->barangay)) !== strtolower(trim($skBarangay))) {
                        $fail('The selected youth must belong to your assigned barangay.');
                    }
                    if (SkOfficial::where('user_id', $value)->exists()) {
                        $fail('The selected youth is already an active SK official.');
                    }
                },
            ],
            'committee' => 'nullable|string|max:255',
            'position' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    $userId = $request->input('user_id');
                    if (! $userId) return;
                    $user = User::with('youthProfile')->find($userId);
                    $barangay = $user?->youthProfile?->barangay;
                    if (! $barangay) return;

                    $count = SkOfficial::where('barangay', $barangay)->where('position', $value)->count();

                    if ($value === 'SK Chairperson' && $count >= 1) {
                        $fail("Barangay $barangay already has an SK Chairperson.");
                    } elseif ($value === 'SK Secretary' && $count >= 1) {
                        $fail("Barangay $barangay already has an SK Secretary.");
                    } elseif ($value === 'SK Treasurer' && $count >= 1) {
                        $fail("Barangay $barangay already has an SK Treasurer.");
                    } elseif ($value === 'SK Kagawad' && $count >= 7) {
                        $fail("Barangay $barangay already has the maximum of 7 SK Kagawads.");
                    }
                },
            ],
            'responsibilities' => 'nullable|string',
            'term' => 'nullable|string|max:255',
        ]);

        $official = $action->execute($validated);

        return response()->json([
            'success' => true,
            'message' => 'SK Official added successfully.',
            'data' => SkOfficialResource::make($official),
        ], 201);
    }

    public function show(Request $request, SkOfficial $skOfficial, GetSkOfficialDetailsAction $action): JsonResponse
    {
        $this->authorizeBarangayOfficial($request->user(), $skOfficial);
        $official = $action->execute($skOfficial->loadMissing('user.youthProfile'));

        return response()->json(SkOfficialResource::make($official));
    }

    public function destroy(Request $request, SkOfficial $skOfficial, DeleteSkOfficialAction $action): JsonResponse
    {
        $this->authorizeBarangayOfficial($request->user(), $skOfficial);
        $action->execute($skOfficial);

        return response()->json([
            'success' => true,
            'message' => 'SK Official removed successfully.',
        ]);
    }
}
