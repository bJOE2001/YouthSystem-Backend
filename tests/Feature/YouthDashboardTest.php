<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\SportsProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class YouthDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_youth_dashboard_counts_earned_certificates_correctly(): void
    {
        $youth = User::factory()->youth()->active()->create(['name' => 'Youth Tester']);
        $admin = User::factory()->admin()->active()->create();

        Sanctum::actingAs($youth);

        // Initially 0 certificates
        $res0 = $this->getJson('/api/youth/dashboard');
        $res0->assertOk()
            ->assertJsonPath('data.cards.certificateEarnd', 0)
            ->assertJsonPath('data.cards.certificateEarned', 0);

        // Create an event with a certificate template
        $event = Event::factory()->create([
            'user_id' => $admin->id,
            'certificate_template_path' => 'certificates/templates/sample_event_cert.jpg',
            'status' => 'completed',
        ]);

        // Youth joins event and attends
        $youth->joinedEvents()->attach($event->id, [
            'attended_at' => now(),
        ]);

        // Dashboard should now show 1 certificate earned
        $res1 = $this->getJson('/api/youth/dashboard');
        $res1->assertOk()
            ->assertJsonPath('data.cards.certificateEarnd', 1)
            ->assertJsonPath('data.cards.certificateEarned', 1);

        // Create a sports program with a certificate template
        $sport = SportsProgram::factory()->create([
            'user_id' => $admin->id,
            'certificate_template_path' => 'certificates/templates/sample_sport_cert.jpg',
            'status' => 'Completed',
        ]);

        // Youth joins sports program and attends
        $youth->joinedSportsPrograms()->attach($sport->id, [
            'attended_at' => now(),
            'team_name' => 'Champions',
        ]);

        // Dashboard should now show 2 certificates earned
        $res2 = $this->getJson('/api/youth/dashboard');
        $res2->assertOk()
            ->assertJsonPath('data.cards.certificateEarnd', 2)
            ->assertJsonPath('data.cards.certificateEarned', 2)
            ->assertJsonPath('data.cards.eventJoined', 2);
    }
}
