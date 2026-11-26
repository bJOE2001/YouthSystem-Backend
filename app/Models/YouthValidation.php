<?php

namespace App\Models;

use App\Enums\YouthProfileStatus;
use Database\Factories\YouthValidationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YouthValidation extends Model
{
    /** @use HasFactory<YouthValidationFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => YouthProfileStatus::Pending->value,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'gender',
        'birth_date',
        'place_of_birth',
        'mobile_number',
        'father_first_name',
        'father_middle_name',
        'father_last_name',
        'mother_first_name',
        'mother_middle_name',
        'mother_last_name',
        'parents_contact_number',
        'guardian_first_name',
        'guardian_last_name',
        'guardian_contact_number',
        'currently_attending_school',
        'senior_high_graduate',
        'educational_attainment',
        'course_strand',
        'ethnicity',
        'religious_affiliation',
        'has_disability',
        'overseas_worker',
        'lgbtq_member',
        'special_youth_sector',
        'attached_id_path',
        'birth_registered',
        'civil_status',
        'solo_parent',
        'barangay',
        'purok_sitio',
        'city',
        'province',
        'postal_code',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'youth_profile_id',
        'source_youth_profile_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'attached_id_path',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function youthProfile(): BelongsTo
    {
        return $this->belongsTo(YouthProfile::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'currently_attending_school' => 'boolean',
            'senior_high_graduate' => 'boolean',
            'has_disability' => 'boolean',
            'overseas_worker' => 'boolean',
            'lgbtq_member' => 'boolean',
            'birth_registered' => 'boolean',
            'solo_parent' => 'boolean',
            'status' => YouthProfileStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }
}
