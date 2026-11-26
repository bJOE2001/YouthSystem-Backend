<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copy pending and rejected youth_profiles into youth_validations idempotently
     * without modifying or deleting any existing approved youth_profiles or users.
     */
    public function up(): void
    {
        if (! Schema::hasTable('youth_validations') || ! Schema::hasTable('youth_profiles')) {
            return;
        }

        DB::transaction(function (): void {
            $baselineApproved = DB::table('youth_profiles')
                ->where('status', 'approved')
                ->orderBy('id')
                ->pluck('user_id', 'id')
                ->all();

            $baselineApprovedUserIds = array_values(array_unique(array_filter($baselineApproved)));
            sort($baselineApprovedUserIds);

            $hasRejectionReason = Schema::hasColumn('youth_profiles', 'rejection_reason');

            $unvalidatedRows = DB::table('youth_profiles')
                ->leftJoin('users', 'users.id', '=', 'youth_profiles.user_id')
                ->whereIn('youth_profiles.status', ['pending', 'rejected'])
                ->select([
                    'youth_profiles.*',
                    'users.email as user_email',
                ])
                ->orderBy('youth_profiles.id')
                ->get();

            foreach ($unvalidatedRows as $row) {
                $alreadyMigrated = DB::table('youth_validations')
                    ->where('source_youth_profile_id', $row->id)
                    ->exists();

                // Never overwrite existing validation decisions on repeated runs.
                if ($alreadyMigrated) {
                    continue;
                }

                if (empty($row->user_email)) {
                    throw new RuntimeException("Cannot backfill youth_profile ID {$row->id}: missing user email.");
                }

                DB::table('youth_validations')->insert([
                    'email' => $row->user_email,
                    'first_name' => $row->first_name,
                    'middle_name' => $row->middle_name,
                    'last_name' => $row->last_name,
                    'suffix' => $row->suffix,
                    'gender' => $row->gender,
                    'birth_date' => $row->birth_date,
                    'place_of_birth' => $row->place_of_birth,
                    'mobile_number' => $row->mobile_number,
                    'father_first_name' => $row->father_first_name,
                    'father_middle_name' => $row->father_middle_name,
                    'father_last_name' => $row->father_last_name,
                    'mother_first_name' => $row->mother_first_name,
                    'mother_middle_name' => $row->mother_middle_name,
                    'mother_last_name' => $row->mother_last_name,
                    'parents_contact_number' => $row->parents_contact_number,
                    'guardian_first_name' => $row->guardian_first_name,
                    'guardian_last_name' => $row->guardian_last_name,
                    'guardian_contact_number' => $row->guardian_contact_number,
                    'currently_attending_school' => $row->currently_attending_school,
                    'senior_high_graduate' => $row->senior_high_graduate,
                    'educational_attainment' => $row->educational_attainment,
                    'course_strand' => $row->course_strand,
                    'ethnicity' => $row->ethnicity,
                    'religious_affiliation' => $row->religious_affiliation,
                    'has_disability' => $row->has_disability,
                    'overseas_worker' => $row->overseas_worker,
                    'lgbtq_member' => $row->lgbtq_member,
                    'special_youth_sector' => $row->special_youth_sector,
                    'attached_id_path' => $row->attached_id_path,
                    'birth_registered' => $row->birth_registered,
                    'civil_status' => $row->civil_status,
                    'solo_parent' => $row->solo_parent,
                    'barangay' => $row->barangay,
                    'purok_sitio' => $row->purok_sitio,
                    'city' => $row->city,
                    'province' => $row->province,
                    'postal_code' => $row->postal_code,
                    'status' => $row->status,
                    'reviewed_by' => $row->reviewed_by,
                    'reviewed_at' => $row->reviewed_at,
                    'rejection_reason' => $hasRejectionReason ? $row->rejection_reason : null,
                    'youth_profile_id' => null,
                    'source_youth_profile_id' => $row->id,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            $postApproved = DB::table('youth_profiles')
                ->where('status', 'approved')
                ->orderBy('id')
                ->pluck('user_id', 'id')
                ->all();

            if ($postApproved !== $baselineApproved) {
                throw new RuntimeException('Approved youth_profiles verification failed during backfill.');
            }

            if ($baselineApprovedUserIds !== []) {
                $existingUserIds = DB::table('users')
                    ->whereIn('id', $baselineApprovedUserIds)
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();

                if ($existingUserIds !== $baselineApprovedUserIds) {
                    throw new RuntimeException('Associated approved users verification failed during backfill.');
                }
            }
        });
    }

    /**
     * Never perform destructive data deletion or modification on rollback.
     */
    public function down(): void
    {
        // Intentionally non-destructive.
    }
};
