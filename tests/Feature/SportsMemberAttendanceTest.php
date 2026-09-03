<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SportsProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SportsMemberAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_mark_attendance_for_sports_team_member_and_leader(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $leader = User::factory()->youth()->active()->create(['name' => 'Captain Jack']);
        $memberUser = User::factory()->youth()->active()->create(['name' => 'Member User']);

        $sportsProgram = SportsProgram::factory()->create([
            'user_id' => $admin->id,
            'name' => 'Basketball League 2026',
            'status' => 'Ongoing',
            'open_to_all_barangays' => true,
        ]);

        // Leader registers a team with members (1 user account, 1 offline member)
        Sanctum::actingAs($leader);
        $joinResponse = $this->postJson("/api/sports/{$sportsProgram->id}/join", [
            'team_name' => 'Tagum Warriors',
            'leader' => [
                'user_id' => $leader->id,
                'name' => $leader->name,
                'email' => $leader->email,
                'role' => 'Team Leader',
            ],
            'teammates' => [
                [
                    'user_id' => $memberUser->id,
                    'name' => $memberUser->name,
                    'email' => $memberUser->email,
                    'role' => 'Member',
                ],
                [
                    'user_id' => null,
                    'name' => 'Offline Member',
                    'email' => 'offline@example.com',
                    'contact' => '09123456789',
                    'role' => 'Member',
                ],
            ],
        ]);
        $joinResponse->assertOk();

        // Admin views participants by barangay
        Sanctum::actingAs($admin);
        $participantsResponse = $this->getJson("/api/sports/{$sportsProgram->id}/participants-by-barangay");
        $participantsResponse->assertOk();

        $allParticipants = [];
        foreach ($participantsResponse->json('data') as $group) {
            foreach ($group['participants'] as $p) {
                $allParticipants[] = $p;
            }
        }

        $this->assertCount(3, $allParticipants);

        $leaderItem = collect($allParticipants)->firstWhere('name', 'Captain Jack');
        $memberUserItem = collect($allParticipants)->firstWhere('name', 'Member User');
        $offlineMemberItem = collect($allParticipants)->firstWhere('name', 'Offline Member');

        $this->assertNotNull($leaderItem);
        $this->assertNotNull($memberUserItem);
        $this->assertNotNull($offlineMemberItem);

        $this->assertEquals('Not Attended', $leaderItem['status']);
        $this->assertEquals('Not Attended', $memberUserItem['status']);
        $this->assertEquals('Not Attended', $offlineMemberItem['status']);

        // Mark attendance for offline member using their id (e.g. tm_...)
        $attendMemberResponse = $this->postJson("/api/events/sport_{$sportsProgram->id}/participants/{$offlineMemberItem['id']}/attend", [
            'name' => $offlineMemberItem['name'],
            'team_name' => $offlineMemberItem['team_name'],
        ]);
        $attendMemberResponse->assertOk();

        // Refetch participants and verify offline member is now Attended, others still Not Attended
        $refetch = $this->getJson("/api/sports/{$sportsProgram->id}/participants-by-barangay");
        $refetch->assertOk();

        $refetchedParticipants = [];
        foreach ($refetch->json('data') as $group) {
            foreach ($group['participants'] as $p) {
                $refetchedParticipants[] = $p;
            }
        }

        $offlineMemberUpdated = collect($refetchedParticipants)->firstWhere('name', 'Offline Member');
        $leaderUpdated = collect($refetchedParticipants)->firstWhere('name', 'Captain Jack');
        $memberUserUpdated = collect($refetchedParticipants)->firstWhere('name', 'Member User');

        $this->assertEquals('Attended', $offlineMemberUpdated['status']);
        $this->assertEquals('Not Attended', $leaderUpdated['status']);
        $this->assertEquals('Not Attended', $memberUserUpdated['status']);

        // Now mark attendance for leader
        $attendLeaderResponse = $this->postJson("/api/events/sport_{$sportsProgram->id}/participants/{$leaderItem['id']}/attend");
        $attendLeaderResponse->assertOk();

        $refetch2 = $this->getJson("/api/sports/{$sportsProgram->id}/participants-by-barangay");
        $refetch2->assertOk();

        $refetched2 = [];
        foreach ($refetch2->json('data') as $group) {
            foreach ($group['participants'] as $p) {
                $refetched2[] = $p;
            }
        }

        $leaderUpdated2 = collect($refetched2)->firstWhere('name', 'Captain Jack');
        $this->assertEquals('Attended', $leaderUpdated2['status']);
    }
}
