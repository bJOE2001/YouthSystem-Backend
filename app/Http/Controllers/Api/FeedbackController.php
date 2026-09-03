<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeedbackResource;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\SportsProgram;
use App\Models\SkOfficial;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Get the assigned barangay of the authenticated SK official.
     */
    protected function getAssignedBarangay(Request $request): ?string
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return SkOfficial::where('email', $user->email)->value('barangay')
            ?? $user->skOfficial?->barangay
            ?? $user->youthProfile?->barangay;
    }

    /**
     * Check if an event or sports program was created by an SK user.
     */
    protected function isCreatedBySk($eventModel, $sportsModel): bool
    {
        if ($eventModel) {
            $role = $eventModel->user?->role;
            $creatorRole = strtolower($role instanceof \BackedEnum ? $role->value : (string)$role);
            if ($creatorRole === 'sk_admin' || $creatorRole === 'sk' || $eventModel->scope === 'barangay' || ! empty($eventModel->barangay)) {
                return true;
            }
        }

        if ($sportsModel) {
            $role = $sportsModel->user?->role;
            $creatorRole = strtolower($role instanceof \BackedEnum ? $role->value : (string)$role);
            if ($creatorRole === 'sk_admin' || $creatorRole === 'sk' || (! empty($sportsModel->barangay) && strtolower($sportsModel->barangay) !== 'all')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Admin: Fetch all feedbacks with search & filter (EXCLUDES SK feedbacks)
     */
    public function index(Request $request)
    {
        $query = Feedback::with(['user.youthProfile', 'event.user', 'sportsProgram.user']);

        // Strictly EXCLUDE SK feedbacks:
        // 1. Not targeted to SK
        $query->where(function ($q) {
            $q->where('target', 'admin')
              ->orWhereNull('target');
        });

        // 2. Event must NOT be created by an sk_admin
        $query->whereDoesntHave('event.user', function ($u) {
            $u->where('role', 'sk_admin');
        });

        // 3. Sports program must NOT be created by an sk_admin
        $query->whereDoesntHave('sportsProgram.user', function ($u) {
            $u->where('role', 'sk_admin');
        });

        // 4. Sports program must not be specific to a barangay
        $query->whereDoesntHave('sportsProgram', function ($sp) {
            $sp->whereNotNull('barangay')
               ->where('barangay', '!=', '')
               ->whereRaw('LOWER(barangay) != ?', ['all']);
        });

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->filled('category') && $request->category !== 'All Categories') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('event', function ($eq) use ($search) {
                        $eq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('sportsProgram', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $feedbacks = $query->latest()->get();

        return FeedbackResource::collection($feedbacks);
    }

    /**
     * SK Admin: Fetch feedbacks for the SK Admin's barangay
     */
    public function skFeedbacks(Request $request)
    {
        $user = $request->user();
        $barangay = $this->getAssignedBarangay($request);

        $query = Feedback::with(['user.youthProfile', 'event.user', 'sportsProgram.user']);

        // Match feedbacks for this SK Admin or their barangay
        $query->where(function ($q) use ($user, $barangay) {
            // A: Event created by this SK admin
            $q->whereHas('event', function ($eq) use ($user) {
                $eq->where('user_id', $user->id);
            });

            // B: Sports program created by this SK admin
            $q->orWhereHas('sportsProgram', function ($sq) use ($user) {
                $sq->where('user_id', $user->id);
            });

            // C: Feedbacks belonging to the assigned barangay
            if ($barangay) {
                $q->orWhere('barangay', $barangay)
                  ->orWhereHas('event', function ($eq) use ($barangay) {
                      $eq->where('barangay', $barangay);
                  })
                  ->orWhereHas('sportsProgram', function ($sq) use ($barangay) {
                      $sq->where('barangay', $barangay);
                  })
                  ->orWhere(function ($sub) use ($barangay) {
                      $sub->where('target', 'sk')
                          ->whereHas('user.youthProfile', function ($yq) use ($barangay) {
                              $yq->where('barangay', $barangay);
                          });
                  });
            }

            // D: Feedbacks explicitly targeting SK for this user's events/sports
            $q->orWhere(function ($sub) use ($user) {
                $sub->where('target', 'sk')
                    ->where(function ($inner) use ($user) {
                        $inner->whereHas('event', fn($eq) => $eq->where('user_id', $user->id))
                              ->orWhereHas('sportsProgram', fn($sq) => $sq->where('user_id', $user->id));
                    });
            });
        });

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->filled('category') && $request->category !== 'All Categories') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('event', function ($eq) use ($search) {
                        $eq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('sportsProgram', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $feedbacks = $query->latest()->get();

        return FeedbackResource::collection($feedbacks);
    }

    /**
     * Youth / SK: Get their own submitted feedbacks
     */
    public function myFeedbacks(Request $request)
    {
        $feedbacks = Feedback::with(['event', 'sportsProgram'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return FeedbackResource::collection($feedbacks);
    }

    /**
     * Youth / SK: Submit a feedback (General, Event, or Sports Activity)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:general,event',
            'target' => 'nullable|string|in:admin,sk',
            'category' => 'required|string|max:255',
            'subject' => 'required|string|min:3|max:255',
            'message' => 'required|string|min:10',
            'is_anonymous' => 'nullable|boolean',
            'event_id' => 'nullable',
            'sports_program_id' => 'nullable',
            'barangay' => 'nullable|string|max:255',
        ]);

        $eventId = null;
        $sportsProgramId = null;
        $eventModel = null;
        $sportsModel = null;

        if ($validated['type'] === 'event' && ! empty($validated['event_id'])) {
            $cleanedId = preg_replace('/^(event_|sport_)/', '', (string) $validated['event_id']);
            $eventModel = Event::with('user')->find($cleanedId);
            if ($eventModel) {
                $eventId = $eventModel->id;
            } else {
                $sportsModel = SportsProgram::with('user')->find($cleanedId);
                if ($sportsModel) {
                    $sportsProgramId = $sportsModel->id;
                }
            }
        }

        if (! empty($validated['sports_program_id'])) {
            $cleanedSportId = preg_replace('/^(event_|sport_)/', '', (string) $validated['sports_program_id']);
            $foundSport = SportsProgram::with('user')->find($cleanedSportId);
            if ($foundSport) {
                $sportsModel = $foundSport;
                $sportsProgramId = $foundSport->id;
            }
        }

        $isSk = $this->isCreatedBySk($eventModel, $sportsModel) || ($validated['target'] ?? '') === 'sk';
        $target = $isSk ? 'sk' : ($validated['target'] ?? 'admin');

        $barangay = $validated['barangay'] ?? null;
        if (! $barangay) {
            $barangay = $eventModel?->barangay
                ?? $sportsModel?->barangay
                ?? $eventModel?->user?->youthProfile?->barangay
                ?? $sportsModel?->user?->youthProfile?->barangay
                ?? $request->user()->youthProfile?->barangay;
        }

        $feedback = Feedback::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'target' => $target,
            'barangay' => $barangay,
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'is_anonymous' => $validated['is_anonymous'] ?? false,
            'event_id' => $eventId,
            'sports_program_id' => $sportsProgramId,
        ]);

        return new FeedbackResource($feedback->load(['user.youthProfile', 'event.user', 'sportsProgram.user']));
    }

    /**
     * Submit feedback directly for an event or sports activity
     */
    public function storeEventFeedback(Request $request, $event)
    {
        $validated = $request->validate([
            'target' => 'nullable|string|in:admin,sk',
            'category' => 'nullable|string|max:255',
            'subject' => 'required|string|min:3|max:255',
            'message' => 'required|string|min:10',
            'is_anonymous' => 'nullable|boolean',
            'barangay' => 'nullable|string|max:255',
        ]);

        $cleanedId = preg_replace('/^(event_|sport_)/', '', (string) $event);
        $eventModel = Event::with('user')->find($cleanedId);
        $sportsModel = null;
        if (! $eventModel) {
            $sportsModel = SportsProgram::with('user')->find($cleanedId);
        }

        if (! $eventModel && ! $sportsModel) {
            return response()->json(['message' => 'Event or activity not found.'], 404);
        }

        $isSk = $this->isCreatedBySk($eventModel, $sportsModel) || ($validated['target'] ?? '') === 'sk';
        $target = $isSk ? 'sk' : ($validated['target'] ?? 'admin');

        $barangay = $validated['barangay'] ?? null;
        if (! $barangay) {
            $barangay = $eventModel?->barangay
                ?? $sportsModel?->barangay
                ?? $eventModel?->user?->youthProfile?->barangay
                ?? $sportsModel?->user?->youthProfile?->barangay
                ?? $request->user()->youthProfile?->barangay;
        }

        $feedback = Feedback::create([
            'user_id' => $request->user()->id,
            'event_id' => $eventModel?->id,
            'sports_program_id' => $sportsModel?->id,
            'type' => 'event',
            'target' => $target,
            'barangay' => $barangay,
            'category' => $validated['category'] ?? 'Event / Activity',
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'is_anonymous' => $validated['is_anonymous'] ?? false,
        ]);

        return new FeedbackResource($feedback->load(['user.youthProfile', 'event.user', 'sportsProgram.user']));
    }

    /**
     * Get feedbacks for a specific event or sports activity
     */
    public function eventFeedbacks($event)
    {
        $cleanedId = preg_replace('/^(event_|sport_)/', '', (string) $event);
        $eventModel = Event::find($cleanedId);
        $sportsModel = null;
        if (! $eventModel) {
            $sportsModel = SportsProgram::find($cleanedId);
        }

        if (! $eventModel && ! $sportsModel) {
            return response()->json(['message' => 'Event or activity not found.'], 404);
        }

        $targetModel = $eventModel ?? $sportsModel;
        $feedbacks = $targetModel->feedbacks()
            ->with(['user.youthProfile', 'event', 'sportsProgram'])
            ->latest()
            ->get();

        return FeedbackResource::collection($feedbacks);
    }
}