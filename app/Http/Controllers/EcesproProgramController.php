<?php

namespace App\Http\Controllers;

use App\Models\EcesproProgram;
use Illuminate\Http\Request;

class EcesproProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EcesproProgram::withCount('applications')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'school_year' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            // 'slots' => 'required|integer|min:1',
            'status' => 'required|string',
            'description' => 'nullable|string',
            // 'benefits' => 'nullable|array',
            // 'benefits.*' => 'string',
            // 'eligibility' => 'nullable|array',
            // 'eligibility.*' => 'string',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string',
        ]);

        $data = $validated;
        // if (array_key_exists('benefits', $data)) {
        //     $data['scholarship_benefits'] = $data['benefits'];
        //     unset($data['benefits']);
        // }
        // if (array_key_exists('eligibility', $data)) {
        //     $data['program_eligibility'] = $data['eligibility'];
        //     unset($data['eligibility']);
        // }
        if (array_key_exists('requirements', $data)) {
            $data['application_requirements'] = $data['requirements'];
            unset($data['requirements']);
        }

        return EcesproProgram::create($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(EcesproProgram $ecesproProgram)
    {
        return $ecesproProgram;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EcesproProgram $ecesproProgram)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'school_year' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            // 'slots' => 'sometimes|integer|min:1',
            'status' => 'sometimes|string',
            'description' => 'nullable|string',
            // 'benefits' => 'nullable|array',
            // 'benefits.*' => 'string',
            // 'eligibility' => 'nullable|array',
            // 'eligibility.*' => 'string',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string',
        ]);

        $data = $validated;
        // if (array_key_exists('benefits', $data)) {
        //     $data['scholarship_benefits'] = $data['benefits'];
        //     unset($data['benefits']);
        // }
        // if (array_key_exists('eligibility', $data)) {
        //     $data['program_eligibility'] = $data['eligibility'];
        //     unset($data['eligibility']);
        // }
        if (array_key_exists('requirements', $data)) {
            $data['application_requirements'] = $data['requirements'];
            unset($data['requirements']);
        }

        $ecesproProgram->update($data);

        if (isset($data['status']) && $data['status'] === 'Open') {
            EcesproProgram::where('id', '!=', $ecesproProgram->id)
                ->where('status', 'Open')
                ->update(['status' => 'Closed']);
        }

        return $ecesproProgram;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EcesproProgram $ecesproProgram)
    {
        $ecesproProgram->delete();

        return response()->noContent();
    }
}
