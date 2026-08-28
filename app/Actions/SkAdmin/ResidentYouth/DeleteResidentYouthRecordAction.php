<?php

namespace App\Actions\SkAdmin\ResidentYouth;

use App\Models\YouthProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeleteResidentYouthRecordAction
{
    /**
     * @throws Throwable
     */
    public function execute(YouthProfile $youthProfile): void
    {
        DB::transaction(function () use ($youthProfile) {
            $user = $youthProfile->user;
            $userEmail = $user?->email;
            $userName = $user?->name;
            $barangay = $youthProfile->barangay;

            $fullName = trim(implode(' ', array_filter([
                $youthProfile->first_name,
                $youthProfile->middle_name,
                $youthProfile->last_name,
                $youthProfile->suffix,
            ])));

            $attachedIdPath = $youthProfile->attached_id_path;

            // Remove associated SK Official record if present
            if (Schema::hasTable('sk_officials')) {
                DB::table('sk_officials')
                    ->where(function ($query) use ($userEmail, $fullName, $userName, $barangay) {
                        $hasCondition = false;

                        if (! empty($userEmail)) {
                            $query->where('email', $userEmail);
                            $hasCondition = true;
                        }

                        if (! empty($fullName)) {
                            $callback = function ($q) use ($fullName, $barangay) {
                                $q->where('name', $fullName);
                                if (! empty($barangay)) {
                                    $q->where('barangay', $barangay);
                                }
                            };
                            if ($hasCondition) {
                                $query->orWhere($callback);
                            } else {
                                $query->where($callback);
                                $hasCondition = true;
                            }
                        }

                        if (! empty($userName) && $userName !== $fullName) {
                            $callback = function ($q) use ($userName, $barangay) {
                                $q->where('name', $userName);
                                if (! empty($barangay)) {
                                    $q->where('barangay', $barangay);
                                }
                            };
                            if ($hasCondition) {
                                $query->orWhere($callback);
                            } else {
                                $query->where($callback);
                            }
                        }
                    })
                    ->delete();
            }

            // Remove associated LYDC Member record if present
            if (Schema::hasTable('lydc_members')) {
                DB::table('lydc_members')
                    ->where(function ($query) use ($userEmail, $fullName, $userName, $barangay) {
                        $hasCondition = false;

                        if (! empty($userEmail)) {
                            $query->where('email', $userEmail);
                            $hasCondition = true;
                        }

                        if (! empty($fullName)) {
                            $callback = function ($q) use ($fullName, $barangay) {
                                $q->where('name', $fullName);
                                if (! empty($barangay)) {
                                    $q->where('barangay', $barangay);
                                }
                            };
                            if ($hasCondition) {
                                $query->orWhere($callback);
                            } else {
                                $query->where($callback);
                                $hasCondition = true;
                            }
                        }

                        if (! empty($userName) && $userName !== $fullName) {
                            $callback = function ($q) use ($userName, $barangay) {
                                $q->where('name', $userName);
                                if (! empty($barangay)) {
                                    $q->where('barangay', $barangay);
                                }
                            };
                            if ($hasCondition) {
                                $query->orWhere($callback);
                            } else {
                                $query->where($callback);
                            }
                        }
                    })
                    ->delete();
            }

            $youthProfile->delete();

            if ($user) {
                if (Schema::hasTable('sports_program_user')) {
                    DB::table('sports_program_user')->where('user_id', $user->id)->delete();
                }

                if (Schema::hasTable('event_user')) {
                    DB::table('event_user')->where('user_id', $user->id)->delete();
                }

                if (Schema::hasTable('ecespro_scholars')) {
                    DB::table('ecespro_scholars')->where('user_id', $user->id)->delete();
                }

                if (Schema::hasTable('feedbacks')) {
                    DB::table('feedbacks')->where('user_id', $user->id)->update(['user_id' => null]);
                }

                if (Schema::hasTable('announcement_user')) {
                    DB::table('announcement_user')->where('user_id', $user->id)->delete();
                }

                $user->delete();
            }

            if ($attachedIdPath !== null) {
                Storage::disk('local')->delete($attachedIdPath);
            }
        });
    }
}
