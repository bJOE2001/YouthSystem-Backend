<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeedbackResource;
use App\Models\Event;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Admin: Fetch all feedbacks with search & filter
     */
    public function index(Request $request)
    {
        $query = Feedback::with(['user.youthProfile', 'event']);

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
        $feedbacks = Feedback::with(['event'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return FeedbackResource::collection($feedbacks);
    }

    /**
     * Youth / SK: Submit a feedback (General or Event)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:general,event',
            'category' => 'required|string|max:255',
            'subject' => 'required|string|min:3|max:255',
            'message' => 'required|string|min:10',
            'is_anonymous' => 'nullable|boolean',
            'event_id' => 'nullable|exists:events,id',
        ]);

        $feedback = Feedback::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'is_anonymous' => $validated['is_anonymous'] ?? false,
            'event_id' => $validated['type'] === 'event' ? ($validated['event_id'] ?? null) : null,
        ]);

        return new FeedbackResource($feedback->load(['user.youthProfile', 'event']));
    }

    /**
     * Submit feedback directly for an event
     */
    public function storeEventFeedback(Request $request, Event $event)
    {
        $validated = $request->validate([
            'category' => 'nullable|string|max:255',
            'subject' => 'required|string|min:3|max:255',
            'message' => 'required|string|min:10',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $feedback = Feedback::create([
            'user_id' => $request->user()->id,
            'event_id' => $event->id,
            'type' => 'event',
            'category' => $validated['category'] ?? 'Event / Activity',
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'is_anonymous' => $validated['is_anonymous'] ?? false,
        ]);

        return new FeedbackResource($feedback->load(['user.youthProfile', 'event']));
    }

    /**
     * Get feedbacks for a specific event
     */
    public function eventFeedbacks(Event $event)
    {
        $feedbacks = $event->feedbacks()
            ->with(['user.youthProfile'])
            ->latest()
            ->get();

        return FeedbackResource::collection($feedbacks);
    }
}
