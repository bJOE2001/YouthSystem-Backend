<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcesproApplication extends Model
{
    protected $fillable = [
        'user_id',
        'ecespro_program_id',

        // Personal Info
        'first_name', 'middle_name', 'last_name', 'suffix', 'gender', 'birthdate', 'age',
        'place_of_birth', 'sex', 'civil_status', 'citizenship', 'personal_zip_code', 'ip_or_muslim',
        'type_of_disability', 'mobile_number', 'email_address', 'permanent_mailing_address',

        // Educational Info
        'previous_grade_college_year_level', 'general_average', 'school_attended_to_enroll',
        'school_address', 'course_intended_to_enroll', 'type_of_school', 'school_year',
        'school_citizenship', 'school', 'year_level', 'course', 'school_zip_code',

        // Father's Info
        'father_last_name', 'father_first_name', 'father_address', 'father_occupation',
        'father_educational_attainment',

        // Mother's Info
        'mother_maiden_middle_name', 'mother_maiden_last_name', 'mother_occupation',
        'mother_educational_attainment',

        // Guardian's Info
        'guardian_maiden_middle_name', 'guardian_maiden_last_name', 'guardian_occupation',
        'guardian_educational_attainment',

        // Other Family Info
        'parents_guardian_total_income', 'number_of_siblings_in_family', 'parents_marital_status',

        // Requirements
        'certificate_of_indigency', 'report_card_grades', 'certificate_of_enrollment',
        'certificate_of_registration', 'good_moral_certificate', 'barangay_clearance',
        'other_supporting_documents',

        'application_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(EcesproProgram::class, 'ecespro_program_id');
    }

    public function examination()
    {
        return $this->hasOne(EcesproExamination::class);
    }

    public function interview()
    {
        return $this->hasOne(EcesproInterview::class);
    }

    public function contract()
    {
        return $this->hasOne(EcesproContract::class);
    }

    public function scholar()
    {
        return $this->hasOne(EcesproScholar::class);
    }
}
