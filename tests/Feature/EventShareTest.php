<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\SportsProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_show_returns_public_share_url(): void
    {
        $admin = User::factory()->admin()->active()->create();

        $event = Event::factory()->create([
            'status' => 'upcoming',
            'user_id' => $admin->id,
        ]);

        $response = $this->getJson("/api/events/event_{$event->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'shareUrl',
                    'share_url',
                ],
            ]);

        $this->assertStringContainsString("/#/activities/event_{$event->id}", $response->json('data.shareUrl'));
    }

    public function test_sports_program_show_returns_public_share_url(): void
    {
        $admin = User::factory()->admin()->active()->create();

        $sport = SportsProgram::factory()->create([
            'status' => 'Upcoming',
            'user_id' => $admin->id,
        ]);

        $response = $this->getJson("/api/events/sport_{$sport->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'shareUrl',
                    'share_url',
                ],
            ]);

        $this->assertStringContainsString("/#/activities/sport_{$sport->id}", $response->json('data.shareUrl'));
    }
}
