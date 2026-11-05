<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SkillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $jobSeekerId = $request->user()?->jobSeeker?->id;

        return response()->json([
            'skills' => Skill::query()->where('is_active', true)
                ->where(fn ($query) => $query->where('is_builtin', true)
                    ->when($jobSeekerId, fn ($owned) => $owned->orWhere('created_by_job_seeker_id', $jobSeekerId)))
                ->orderBy('type')->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'job_seeker', 403);

        $attributes = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'type' => ['required', Rule::in(['hard', 'soft'])],
        ]);
        $attributes['name'] = trim($attributes['name']);

        $jobSeeker = $request->user()->jobSeeker;
        $skill = Skill::query()->where('type', $attributes['type'])->where('name', $attributes['name'])
            ->where(fn ($query) => $query->where('is_builtin', true)
                ->orWhere('created_by_job_seeker_id', $jobSeeker->id))
            ->first();
        if (! $skill) {
            $skill = Skill::create([
                ...$attributes,
                'created_by_job_seeker_id' => $jobSeeker->id,
                'is_builtin' => false,
                'is_active' => true,
            ]);
        }
        $jobSeeker->skillCatalog()->syncWithoutDetaching([$skill->id]);

        return response()->json(['skill' => $skill->only(['id', 'name', 'type'])], $skill->wasRecentlyCreated ? 201 : 200);
    }
}
