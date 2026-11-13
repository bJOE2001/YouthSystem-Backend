<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackQuestionnaire;
use App\Models\FeedbackResponse;
use App\Models\SkOfficial;
use App\Models\SportsProgram;
use App\Notifications\FeedbackQuestionnaireAssignedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FeedbackQuestionnaireController extends Controller
{
    /**
     * Resolve the assigned barangay of the authenticated user.
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
     * List all questionnaires (filterable by target, barangay, scope, search).
     */
    public function index(Request $request)
    {
        $query = FeedbackQuestionnaire::with('questions');

        // Target filter ('admin' or 'sk')
        if ($request->filled('target') && $request->target !== 'all') {
            $query->where('target', $request->target);
        }

        // Barangay filter
        if ($request->filled('barangay')) {
            $barangay = strtolower(trim($request->barangay));
            $query->where(function ($q) use ($barangay) {
                $q->whereNull('barangay')
                    ->orWhereRaw('LOWER(barangay) = ?', [$barangay]);
            });
        }

        // Scope filter ('event', 'sports', 'general', 'all')
        if ($request->filled('scope') && $request->scope !== 'all') {
            if ($request->scope === 'general') {
                $query->where('scope', 'general');
            } else {
                $query->where(function ($q) use ($request) {
                    $q->where('scope', 'all')
                        ->orWhere('scope', $request->scope);
                });
            }
        }

        // Active only filter
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Search query
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($request->per_page ?? 50);
        $questionnaires = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $questionnaires->items(),
            'meta' => [
                'total' => $questionnaires->total(),
                'current_page' => $questionnaires->currentPage(),
                'per_page' => $questionnaires->perPage(),
                'last_page' => $questionnaires->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new questionnaire template with its questions.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|min:3|max:255',
            'description' => 'nullable|string',
            'target' => 'nullable|string|in:admin,sk',
            'barangay' => 'nullable|string|max:255',
            'scope' => 'nullable|string|in:event,sports,general,all',
            'is_active' => 'nullable|boolean',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|string|in:rating_5,single_choice,multiple_choice,text',
            'questions.*.options' => 'nullable|array',
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.sort_order' => 'nullable|integer',
        ]);

        $user = $request->user();
        $isSk = ($user->role === 'sk_admin' || $user->role === 'sk') || ($validated['target'] ?? '') === 'sk';
        $target = $isSk ? 'sk' : ($validated['target'] ?? 'admin');
        $barangay = $validated['barangay'] ?? ($isSk ? $this->getAssignedBarangay($request) : null);

        $questionnaire = DB::transaction(function () use ($validated, $user, $target, $barangay) {
            $q = FeedbackQuestionnaire::create([
                'user_id' => $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'target' => $target,
                'barangay' => $barangay,
                'scope' => $validated['scope'] ?? 'all',
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['questions'] as $idx => $qData) {
                FeedbackQuestion::create([
                    'questionnaire_id' => $q->id,
                    'question_text' => $qData['question_text'],
                    'question_type' => $qData['question_type'],
                    'options' => $qData['options'] ?? null,
                    'is_required' => $qData['is_required'] ?? true,
                    'sort_order' => $qData['sort_order'] ?? ($idx + 1),
                ]);
            }

            return $q;
        });

        return response()->json([
            'data' => $questionnaire->load('questions'),
            'message' => 'Questionnaire template created successfully.',
        ], 201);
    }

    /**
     * Show a single questionnaire.
     */
    public function show($id)
    {
        $questionnaire = FeedbackQuestionnaire::with('questions')->findOrFail($id);

        return response()->json([
            'data' => $questionnaire,
        ]);
    }

    /**
     * Update an existing questionnaire.
     */
    public function update(Request $request, $id)
    {
        $questionnaire = FeedbackQuestionnaire::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|min:3|max:255',
            'description' => 'nullable|string',
            'scope' => 'nullable|string|in:event,sports,general,all',
            'is_active' => 'nullable|boolean',
            'questions' => 'nullable|array',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|string|in:rating_5,single_choice,multiple_choice,text',
            'questions.*.options' => 'nullable|array',
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.sort_order' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($questionnaire, $validated) {
            $questionnaire->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? $questionnaire->description,
                'scope' => $validated['scope'] ?? $questionnaire->scope,
                'is_active' => $validated['is_active'] ?? $questionnaire->is_active,
            ]);

            if (isset($validated['questions'])) {
                // Delete existing and re-insert updated questions
                $questionnaire->questions()->delete();
                foreach ($validated['questions'] as $idx => $qData) {
                    FeedbackQuestion::create([
                        'questionnaire_id' => $questionnaire->id,
                        'question_text' => $qData['question_text'],
                        'question_type' => $qData['question_type'],
                        'options' => $qData['options'] ?? null,
                        'is_required' => $qData['is_required'] ?? true,
                        'sort_order' => $qData['sort_order'] ?? ($idx + 1),
                    ]);
                }
            }
        });

        return response()->json([
            'data' => $questionnaire->fresh(['questions']),
            'message' => 'Questionnaire template updated successfully.',
        ]);
    }

    /**
     * Delete a questionnaire.
     */
    public function destroy($id)
    {
        $questionnaire = FeedbackQuestionnaire::findOrFail($id);
        $questionnaire->delete();

        return response()->json([
            'success' => true,
            'message' => 'Questionnaire deleted successfully.',
        ]);
    }

    /**
     * Assign a questionnaire as active TCYSDO Office or Barangay Feedback.
     */
    public function assignGeneral(Request $request, $id)
    {
        $questionnaire = FeedbackQuestionnaire::findOrFail($id);

        if ($questionnaire->target === 'sk') {
            $barangay = $questionnaire->barangay ?? $this->getAssignedBarangay($request);
            FeedbackQuestionnaire::where('target', 'sk')
                ->where(function ($q) use ($barangay) {
                    if ($barangay) {
                        $q->whereRaw('LOWER(barangay) = ?', [strtolower(trim($barangay))]);
                    } else {
                        $q->whereNull('barangay');
                    }
                })
                ->where('id', '!=', $questionnaire->id)
                ->update(['is_assigned_general' => false]);
        } else {
            FeedbackQuestionnaire::where('target', 'admin')
                ->where('id', '!=', $questionnaire->id)
                ->update(['is_assigned_general' => false]);
        }

        $questionnaire->update([
            'scope' => 'general',
            'is_assigned_general' => true,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => $questionnaire->target === 'sk'
                ? 'Questionnaire assigned as Barangay Feedback successfully.'
                : 'Questionnaire assigned as TCYSDO Office Feedback successfully.',
            'data' => $questionnaire->fresh(['questions']),
        ]);
    }

    /**
     * Unassign a questionnaire from being active TCYSDO Office or Barangay Feedback.
     */
    public function unassignGeneral(Request $request, $id)
    {
        $questionnaire = FeedbackQuestionnaire::findOrFail($id);

        $questionnaire->update([
            'is_assigned_general' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => $questionnaire->target === 'sk'
                ? 'Questionnaire unassigned from Barangay Feedback.'
                : 'Questionnaire unassigned from TCYSDO Office Feedback.',
            'data' => $questionnaire->fresh(['questions']),
        ]);
    }

    /**
     * Set or clear default questionnaire status for Events, Sports, or Both.
     */
    public function setDefault(Request $request, $id)
    {
        $questionnaire = FeedbackQuestionnaire::findOrFail($id);

        $validated = $request->validate([
            'default_for' => 'nullable|string|in:events,sports,both,none',
        ]);

        $defaultFor = $validated['default_for'] ?? null;
        if ($defaultFor === 'none' || empty($defaultFor)) {
            $defaultFor = null;
        }

        if ($defaultFor) {
            $query = FeedbackQuestionnaire::where('target', $questionnaire->target);
            if ($questionnaire->target === 'sk') {
                $barangay = $questionnaire->barangay ?? $this->getAssignedBarangay($request);
                if ($barangay) {
                    $query->whereRaw('LOWER(barangay) = ?', [strtolower(trim($barangay))]);
                } else {
                    $query->whereNull('barangay');
                }
            }
            $query->where('id', '!=', $questionnaire->id);

            if ($defaultFor === 'both') {
                $query->whereIn('default_for', ['events', 'sports', 'both'])
                    ->update(['default_for' => null]);
            } elseif ($defaultFor === 'events') {
                $existingBoth = (clone $query)->where('default_for', 'both')->get();
                foreach ($existingBoth as $item) {
                    $item->update(['default_for' => 'sports']);
                }
                (clone $query)->where('default_for', 'events')->update(['default_for' => null]);
            } elseif ($defaultFor === 'sports') {
                $existingBoth = (clone $query)->where('default_for', 'both')->get();
                foreach ($existingBoth as $item) {
                    $item->update(['default_for' => 'events']);
                }
                (clone $query)->where('default_for', 'sports')->update(['default_for' => null]);
            }
        }

        $questionnaire->update([
            'default_for' => $defaultFor,
            'is_active' => true,
        ]);

        $label = match ($defaultFor) {
            'events' => 'Events Only',
            'sports' => 'Sports Programs',
            'both' => 'Both Events and Sports',
            default => 'None',
        };

        return response()->json([
            'success' => true,
            'message' => $defaultFor
                ? "Questionnaire successfully set as Default for {$label}."
                : 'Default status removed from questionnaire.',
            'data' => $questionnaire->fresh(['questions']),
        ]);
    }

    /**
     * Assign a questionnaire to a completed Event & notify participants.
     */
    public function assignToEvent(Request $request, $event)
    {
        $cleanedId = preg_replace('/^(event_|sport_)/', '', (string) $event);
        $eventModel = Event::findOrFail($cleanedId);

        $validated = $request->validate([
            'questionnaire_id' => 'required|exists:feedback_questionnaires,id',
            'notify_participants' => 'nullable|boolean',
        ]);

        $questionnaire = FeedbackQuestionnaire::with('questions')->findOrFail($validated['questionnaire_id']);

        $eventModel->update([
            'feedback_questionnaire_id' => $questionnaire->id,
            'feedback_questionnaire_assigned_at' => now(),
        ]);

        $notify = $validated['notify_participants'] ?? true;
        if ($notify) {
            $participants = $eventModel->participants()->wherePivotNotNull('attended_at')->get();
            if ($participants->isNotEmpty()) {
                Notification::send($participants, new FeedbackQuestionnaireAssignedNotification($eventModel, $questionnaire));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Questionnaire assigned to event successfully.',
            'event' => $eventModel->fresh(['feedbackQuestionnaire.questions']),
        ]);
    }

    /**
     * Get assigned questionnaire for an Event.
     */
    public function getEventQuestionnaire($event)
    {
        $cleanedId = preg_replace('/^(event_|sport_)/', '', (string) $event);
        $eventModel = Event::findOrFail($cleanedId);

        $questionnaire = $eventModel->feedbackQuestionnaire()->with('questions')->first();

        $isDefault = false;
        if (! $questionnaire) {
            $defaultQuery = FeedbackQuestionnaire::where('is_active', true)
                ->whereIn('default_for', ['events', 'both']);

            $creator = $eventModel->user;
            $isSk = $eventModel->target === 'sk' || ($creator && ($creator->role === 'sk_admin' || $creator->role?->value === 'sk_admin'));
            if ($isSk || !empty($eventModel->barangay)) {
                $b = $eventModel->barangay ?? $creator?->skOfficial?->barangay;
                $defaultQuery->where('target', 'sk')
                    ->where(function ($q) use ($b) {
                        if ($b) {
                            $q->whereRaw('LOWER(barangay) = ?', [strtolower(trim($b))]);
                        } else {
                            $q->whereNull('barangay');
                        }
                    });
            } else {
                $defaultQuery->where('target', 'admin');
            }
            $questionnaire = $defaultQuery->with('questions')->first();
            if ($questionnaire) {
                $isDefault = true;
            }
        }

        return response()->json([
            'data' => [
                'activity_id' => $eventModel->id,
                'activity_type' => 'event',
                'activity_name' => $eventModel->name,
                'questionnaireId' => $questionnaire?->id,
                'assignedAt' => $eventModel->feedback_questionnaire_assigned_at,
                'is_default' => $isDefault,
                'questionnaire' => $questionnaire,
            ],
        ]);
    }

    /**
     * Assign a questionnaire to a completed Sports Program & notify participants.
     */
    public function assignToSportsProgram(Request $request, $sportsProgram)
    {
        $cleanedId = preg_replace('/^(event_|sport_)/', '', (string) $sportsProgram);
        $sportsModel = SportsProgram::findOrFail($cleanedId);

        $validated = $request->validate([
            'questionnaire_id' => 'required|exists:feedback_questionnaires,id',
            'notify_participants' => 'nullable|boolean',
        ]);

        $questionnaire = FeedbackQuestionnaire::with('questions')->findOrFail($validated['questionnaire_id']);

        $sportsModel->update([
            'feedback_questionnaire_id' => $questionnaire->id,
            'feedback_questionnaire_assigned_at' => now(),
        ]);

        $notify = $validated['notify_participants'] ?? true;
        if ($notify) {
            $participants = $sportsModel->participants()->wherePivotNotNull('attended_at')->get();
            if ($participants->isNotEmpty()) {
                Notification::send($participants, new FeedbackQuestionnaireAssignedNotification($sportsModel, $questionnaire));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Questionnaire assigned to sports program successfully.',
            'sports_program' => $sportsModel->fresh(['feedbackQuestionnaire.questions']),
        ]);
    }

    /**
     * Get assigned questionnaire for a Sports Program.
     */
    public function getSportsProgramQuestionnaire($sportsProgram)
    {
        $cleanedId = preg_replace('/^(event_|sport_)/', '', (string) $sportsProgram);
        $sportsModel = SportsProgram::findOrFail($cleanedId);

        $questionnaire = $sportsModel->feedbackQuestionnaire()->with('questions')->first();

        $isDefault = false;
        if (! $questionnaire) {
            $defaultQuery = FeedbackQuestionnaire::where('is_active', true)
                ->whereIn('default_for', ['sports', 'both']);

            $creator = $sportsModel->user;
            $isSk = $sportsModel->target === 'sk' || ($creator && ($creator->role === 'sk_admin' || $creator->role?->value === 'sk_admin'));
            if ($isSk || !empty($sportsModel->barangay)) {
                $b = $sportsModel->barangay ?? $creator?->skOfficial?->barangay;
                $defaultQuery->where('target', 'sk')
                    ->where(function ($q) use ($b) {
                        if ($b) {
                            $q->whereRaw('LOWER(barangay) = ?', [strtolower(trim($b))]);
                        } else {
                            $q->whereNull('barangay');
                        }
                    });
            } else {
                $defaultQuery->where('target', 'admin');
            }
            $questionnaire = $defaultQuery->with('questions')->first();
            if ($questionnaire) {
                $isDefault = true;
            }
        }

        return response()->json([
            'data' => [
                'activity_id' => $sportsModel->id,
                'activity_type' => 'sport',
                'activity_name' => $sportsModel->name,
                'questionnaireId' => $questionnaire?->id,
                'assignedAt' => $sportsModel->feedback_questionnaire_assigned_at,
                'is_default' => $isDefault,
                'questionnaire' => $questionnaire,
            ],
        ]);
    }

    /**
     * Submit an evaluation response to a questionnaire.
     */
    public function storeResponse(Request $request, $id)
    {
        $questionnaire = FeedbackQuestionnaire::findOrFail($id);

        $validated = $request->validate([
            'answers' => 'required|array',
            'is_anonymous' => 'nullable|boolean',
            'general_remarks' => 'nullable|string',
            'average_rating' => 'nullable|numeric',
            'activity_type' => 'nullable|string',
            'activity_id' => 'nullable',
            'activity_name' => 'nullable|string',
            'target' => 'nullable|string|in:admin,sk',
            'barangay' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $isAnonymous = $validated['is_anonymous'] ?? false;

        $eventId = null;
        $sportsProgramId = null;

        if (! empty($validated['activity_id']) && ($validated['activity_type'] ?? '') !== 'platform') {
            $cleanedId = preg_replace('/^(event_|sport_)/', '', (string) $validated['activity_id']);
            if (($validated['activity_type'] ?? '') === 'sport') {
                $sportsProgramId = $cleanedId;
                $sportsModel = SportsProgram::find($sportsProgramId);
                if ($sportsModel && strtolower((string) $sportsModel->status) !== 'completed') {
                    return response()->json([
                        'message' => 'Feedback evaluation can only be submitted once the sports activity is marked as Completed.',
                    ], 422);
                }

                // Verify user attendance for sports program
                if ($user && $user->role !== 'admin' && $user->role !== 'sk_admin' && ($user->role?->value ?? '') !== 'admin' && ($user->role?->value ?? '') !== 'sk_admin') {
                    $attended = DB::table('sports_program_user')
                        ->where('sports_program_id', $sportsProgramId)
                        ->where('user_id', $user->id)
                        ->whereNotNull('attended_at')
                        ->exists();

                    if (! $attended) {
                        return response()->json([
                            'message' => 'You cannot submit feedback for a sports activity you did not attend.',
                        ], 403);
                    }
                }
            } else {
                $eventId = $cleanedId;
                $eventModel = Event::find($eventId);
                if ($eventModel && strtolower((string) $eventModel->status) !== 'completed') {
                    return response()->json([
                        'message' => 'Feedback evaluation can only be submitted once the event is marked as Completed.',
                    ], 422);
                }

                // Verify user attendance for event
                if ($user && $user->role !== 'admin' && $user->role !== 'sk_admin' && ($user->role?->value ?? '') !== 'admin' && ($user->role?->value ?? '') !== 'sk_admin') {
                    $attended = DB::table('event_user')
                        ->where('event_id', $eventId)
                        ->where('user_id', $user->id)
                        ->whereNotNull('attended_at')
                        ->exists();

                    if (! $attended) {
                        return response()->json([
                            'message' => 'You cannot submit feedback for an event you did not attend.',
                        ], 403);
                    }
                }
            }
        }

        $response = FeedbackResponse::create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => $user?->id,
            'event_id' => $eventId,
            'sports_program_id' => $sportsProgramId,
            'activity_type' => $validated['activity_type'] ?? 'event',
            'activity_name' => $validated['activity_name'] ?? $questionnaire->title,
            'target' => $validated['target'] ?? $questionnaire->target,
            'barangay' => $validated['barangay'] ?? $questionnaire->barangay,
            'is_anonymous' => $isAnonymous,
            'submitted_by_name' => $isAnonymous ? 'Anonymous Participant' : ($user?->name ?? 'Youth Member'),
            'user_name' => $isAnonymous ? 'Anonymous Participant' : ($user?->name ?? 'Youth Member'),
            'user_email' => $isAnonymous ? null : $user?->email,
            'average_rating' => $validated['average_rating'] ?? null,
            'general_remarks' => $validated['general_remarks'] ?? null,
            'answers' => $validated['answers'],
        ]);

        return response()->json([
            'data' => $response,
            'message' => 'Thank you! Your feedback evaluation has been submitted successfully.',
        ], 201);
    }

    /**
     * List all questionnaire responses (filterable for Admin and SK dashboard reviews).
     */
    public function getResponses(Request $request)
    {
        $query = FeedbackResponse::with(['questionnaire', 'user.youthProfile', 'event', 'sportsProgram']);

        if ($request->filled('target') && $request->target !== 'all') {
            $query->where('target', $request->target);
        }

        if ($request->filled('barangay')) {
            $barangay = strtolower(trim($request->barangay));
            $query->where(function ($q) use ($barangay) {
                $q->whereNull('barangay')
                    ->orWhereRaw('LOWER(barangay) = ?', [$barangay]);
            });
        }

        if ($request->filled('questionnaire_id')) {
            $query->where('questionnaire_id', $request->questionnaire_id);
        }

        if ($request->filled('activity_id')) {
            if ($request->activity_id === 'general') {
                $query->whereNull('event_id')->whereNull('sports_program_id');
            } else {
                $actId = preg_replace('/^(event_|sport_)/', '', (string) $request->activity_id);
                $actType = $request->activity_type;
                if ($actType === 'event') {
                    $query->where('event_id', $actId);
                } elseif ($actType === 'sport') {
                    $query->where('sports_program_id', $actId);
                } else {
                    $query->where(function ($q) use ($actId) {
                        $q->where('event_id', $actId)
                            ->orWhere('sports_program_id', $actId);
                    });
                }
            }
        } elseif ($request->activity_type === 'general') {
            $query->whereNull('event_id')->whereNull('sports_program_id');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('activity_name', 'like', "%{$search}%")
                    ->orWhere('submitted_by_name', 'like', "%{$search}%")
                    ->orWhere('general_remarks', 'like', "%{$search}%")
                    ->orWhereHas('questionnaire', function ($qq) use ($search) {
                        $qq->where('title', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) ($request->per_page ?? 50);
        $responses = $query->latest()->paginate($perPage);

        // Mask user info on anonymous submissions
        $responses->getCollection()->transform(function ($resp) {
            if ($resp->is_anonymous) {
                $resp->setRelation('user', null);
                $resp->user_id = null;
            }
            return $resp;
        });

        return response()->json([
            'data' => $responses->items(),
            'meta' => [
                'total' => $responses->total(),
                'current_page' => $responses->currentPage(),
                'per_page' => $responses->perPage(),
                'last_page' => $responses->lastPage(),
            ],
        ]);
    }

    /**
     * Check pending questionnaires for authenticated youth user.
     */
    public function myPending(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['data' => []]);
        }

        // 1. Events attended by user that are Completed
        $attendedEvents = Event::whereRaw('LOWER(status) = ?', ['completed'])
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                    ->whereNotNull('event_user.attended_at');
            })
            ->with(['feedbackQuestionnaire.questions', 'user.skOfficial'])
            ->get();

        // 2. Sports programs attended by user that are Completed
        $attendedSports = SportsProgram::whereRaw('LOWER(status) = ?', ['completed'])
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                    ->whereNotNull('sports_program_user.attended_at');
            })
            ->with(['feedbackQuestionnaire.questions', 'user.skOfficial'])
            ->get();

        // 3. User's already submitted responses
        $submittedEventIds = FeedbackResponse::where('user_id', $user->id)
            ->whereNotNull('event_id')
            ->pluck('event_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $submittedSportIds = FeedbackResponse::where('user_id', $user->id)
            ->whereNotNull('sports_program_id')
            ->pluck('sports_program_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $pending = [];

        foreach ($attendedEvents as $evt) {
            if (in_array((string) $evt->id, $submittedEventIds, true)) {
                continue;
            }
            $questionnaire = $evt->feedbackQuestionnaire;
            if (! $questionnaire) {
                $defaultQuery = FeedbackQuestionnaire::where('is_active', true)
                    ->whereIn('default_for', ['events', 'both']);
                $creator = $evt->user;
                $isSk = $evt->target === 'sk' || ($creator && ($creator->role === 'sk_admin' || $creator->role?->value === 'sk_admin'));
                if ($isSk || !empty($evt->barangay)) {
                    $b = $evt->barangay ?? $creator?->skOfficial?->barangay;
                    $defaultQuery->where('target', 'sk')
                        ->where(function ($q) use ($b) {
                            if ($b) {
                                $q->whereRaw('LOWER(barangay) = ?', [strtolower(trim($b))]);
                            } else {
                                $q->whereNull('barangay');
                            }
                        });
                } else {
                    $defaultQuery->where('target', 'admin');
                }
                $questionnaire = $defaultQuery->with('questions')->first();
            }

            if (! $questionnaire) {
                continue;
            }

            $pending[] = [
                'activity_id' => (string) $evt->id,
                'activity_type' => 'event',
                'activity_name' => $evt->name,
                'name' => $evt->name,
                'title' => $evt->name,
                'barangay' => $evt->barangay,
                'organizer_name' => $evt->barangay ? "SK {$evt->barangay}" : 'TCYSDO',
                'date_time' => $evt->start_date ? $evt->start_date->format('M d, Y') : 'Completed',
                'dateTime' => $evt->start_date ? $evt->start_date->format('M d, Y') : 'Completed',
                'location' => $evt->location ?? 'Tagum City',
                'questionnaire' => $questionnaire,
            ];
        }

        foreach ($attendedSports as $sport) {
            if (in_array((string) $sport->id, $submittedSportIds, true)) {
                continue;
            }
            $questionnaire = $sport->feedbackQuestionnaire;
            if (! $questionnaire) {
                $defaultQuery = FeedbackQuestionnaire::where('is_active', true)
                    ->whereIn('default_for', ['sports', 'both']);
                $creator = $sport->user;
                $isSk = $sport->target === 'sk' || ($creator && ($creator->role === 'sk_admin' || $creator->role?->value === 'sk_admin'));
                if ($isSk || !empty($sport->barangay)) {
                    $b = $sport->barangay ?? $creator?->skOfficial?->barangay;
                    $defaultQuery->where('target', 'sk')
                        ->where(function ($q) use ($b) {
                            if ($b) {
                                $q->whereRaw('LOWER(barangay) = ?', [strtolower(trim($b))]);
                            } else {
                                $q->whereNull('barangay');
                            }
                        });
                } else {
                    $defaultQuery->where('target', 'admin');
                }
                $questionnaire = $defaultQuery->with('questions')->first();
            }

            if (! $questionnaire) {
                continue;
            }

            $pending[] = [
                'activity_id' => (string) $sport->id,
                'activity_type' => 'sport',
                'activity_name' => $sport->name,
                'name' => $sport->name,
                'title' => $sport->name,
                'barangay' => $sport->barangay,
                'organizer_name' => $sport->barangay ? "SK {$sport->barangay}" : 'TCYSDO',
                'date_time' => $sport->start_date ? $sport->start_date->format('M d, Y') : 'Completed',
                'dateTime' => $sport->start_date ? $sport->start_date->format('M d, Y') : 'Completed',
                'location' => $sport->location ?? 'Tagum City',
                'questionnaire' => $questionnaire,
            ];
        }

        $submittedQuestionnaireIds = FeedbackResponse::where('user_id', $user->id)
            ->pluck('questionnaire_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        return response()->json([
            'data' => $pending,
            'submitted_questionnaire_ids' => $submittedQuestionnaireIds,
        ]);
    }

    /**
     * Get aggregated feedback activity summaries for Events and Sports Programs.
     */
    public function getActivitySummaries(Request $request)
    {
        $user = $request->user();
        $target = $request->query('target', 'admin');
        $barangay = $request->query('barangay');

        if ($user && ($user->role === 'sk_admin' || $user->role === 'sk' || $target === 'sk')) {
            $barangay = $barangay
                ?: ($user->skOfficial?->barangay ?? $user->youthProfile?->barangay);
        }

        // 1. Preload aggregate response statistics for events and sports
        $eventStats = FeedbackResponse::whereNotNull('event_id')
            ->groupBy('event_id')
            ->selectRaw('event_id, COUNT(*) as total_responses, AVG(average_rating) as avg_rating, MAX(created_at) as last_response_at')
            ->get()
            ->keyBy('event_id');

        $sportsStats = FeedbackResponse::whereNotNull('sports_program_id')
            ->groupBy('sports_program_id')
            ->selectRaw('sports_program_id, COUNT(*) as total_responses, AVG(average_rating) as avg_rating, MAX(created_at) as last_response_at')
            ->get()
            ->keyBy('sports_program_id');

        $activeEventIdsWithResponses = $eventStats->keys()->toArray();
        $activeSportIdsWithResponses = $sportsStats->keys()->toArray();

        // 2. Query Events
        $eventsQuery = Event::with('feedbackQuestionnaire')
            ->where(function ($q) use ($activeEventIdsWithResponses) {
                $q->whereNotNull('feedback_questionnaire_id')
                    ->orWhereIn('id', $activeEventIdsWithResponses);
            });

        if ($barangay) {
            $brgy = strtolower(trim($barangay));
            $eventsQuery->where(function ($q) use ($brgy) {
                $q->whereRaw('LOWER(barangay) = ?', [$brgy])
                    ->orWhereHas('user', function ($uq) use ($brgy) {
                        $uq->whereHas('skOfficial', fn($sq) => $sq->whereRaw('LOWER(barangay) = ?', [$brgy]));
                    });
            });
        } elseif ($target === 'admin') {
            $eventsQuery->where(function ($q) {
                $q->whereNull('barangay')
                    ->orWhere('barangay', '')
                    ->orWhere('barangay', 'All')
                    ->orWhere('barangay', 'City-wide');
            });
        }

        $events = $eventsQuery->orderBy('start_date', 'desc')->get();

        // 3. Query Sports Programs
        $sportsQuery = SportsProgram::with('feedbackQuestionnaire')
            ->where(function ($q) use ($activeSportIdsWithResponses) {
                $q->whereNotNull('feedback_questionnaire_id')
                    ->orWhereIn('id', $activeSportIdsWithResponses);
            });

        if ($barangay) {
            $brgy = strtolower(trim($barangay));
            $sportsQuery->where(function ($q) use ($brgy) {
                $q->whereRaw('LOWER(barangay) = ?', [$brgy])
                    ->orWhereHas('user', function ($uq) use ($brgy) {
                        $uq->whereHas('skOfficial', fn($sq) => $sq->whereRaw('LOWER(barangay) = ?', [$brgy]));
                    });
            });
        } elseif ($target === 'admin') {
            $sportsQuery->where(function ($q) {
                $q->whereNull('barangay')
                    ->orWhere('barangay', '')
                    ->orWhere('barangay', 'All')
                    ->orWhere('barangay', 'City-wide');
            });
        }

        $sports = $sportsQuery->orderBy('start_date', 'desc')->get();

        $summaries = [];

        foreach ($events as $evt) {
            $stat = $eventStats->get($evt->id);
            $totalResponses = $stat ? (int) $stat->total_responses : 0;
            $avgRating = $stat && $stat->avg_rating !== null ? round((float) $stat->avg_rating, 1) : null;
            $lastAt = $stat ? $stat->last_response_at : null;

            $summaries[] = [
                'activity_id' => (string) $evt->id,
                'activity_type' => 'event',
                'activity_name' => $evt->name,
                'barangay' => $evt->barangay,
                'organizer' => $evt->barangay ? "SK {$evt->barangay}" : 'TCYSDO',
                'date_time' => $evt->start_date ? $evt->start_date->format('M d, Y') : 'Completed Activity',
                'location' => $evt->location ?? 'Tagum City',
                'questionnaire_id' => $evt->feedback_questionnaire_id,
                'questionnaire_title' => $evt->feedbackQuestionnaire?->title ?? 'Evaluation Questionnaire',
                'total_responses' => $totalResponses,
                'average_rating' => $avgRating,
                'last_response_at' => $lastAt,
            ];
        }

        foreach ($sports as $sport) {
            $stat = $sportsStats->get($sport->id);
            $totalResponses = $stat ? (int) $stat->total_responses : 0;
            $avgRating = $stat && $stat->avg_rating !== null ? round((float) $stat->avg_rating, 1) : null;
            $lastAt = $stat ? $stat->last_response_at : null;

            $summaries[] = [
                'activity_id' => (string) $sport->id,
                'activity_type' => 'sport',
                'activity_name' => $sport->name,
                'barangay' => $sport->barangay,
                'organizer' => $sport->barangay ? "SK {$sport->barangay}" : 'TCYSDO',
                'date_time' => $sport->start_date ? $sport->start_date->format('M d, Y') : 'Completed Program',
                'location' => $sport->location ?? 'Tagum City',
                'questionnaire_id' => $sport->feedback_questionnaire_id,
                'questionnaire_title' => $sport->feedbackQuestionnaire?->title ?? 'Evaluation Questionnaire',
                'total_responses' => $totalResponses,
                'average_rating' => $avgRating,
                'last_response_at' => $lastAt,
            ];
        }

        // Sort by total_responses desc then activity_name
        usort($summaries, function ($a, $b) {
            if ($a['total_responses'] !== $b['total_responses']) {
                return $b['total_responses'] <=> $a['total_responses'];
            }
            return strcmp($a['activity_name'], $b['activity_name']);
        });

        return response()->json([
            'data' => $summaries,
        ]);
    }
}
