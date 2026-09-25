<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\Region;
use App\Models\User;

class AuthController extends Controller
{
    public function home(): View
    {
        return $this->appView();
    }

    public function searchPage(): View
    {
        return $this->appView(null, 'search');
    }

    public function profilePage(): View
    {
        abort_unless(Auth::user()?->role === 'job_seeker', 403);
        return $this->appView(null, 'profile');
    }

    public function showLogin(): View
    {
        return $this->appView('login');
    }

    public function showRegister(): View
    {
        return $this->appView('register');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'The supplied credentials are incorrect.'])->onlyInput('email');
        }

        if (! $request->user()->status) {
            Auth::logout();
            return back()->withErrors(['email' => 'This account is inactive.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        return redirect()->intended('/');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'experiences' => collect($request->input('experiences', []))->filter(fn ($row) => filled($row['job_title'] ?? null) || filled($row['employer_name'] ?? null))->values()->all(),
            'educations' => collect($request->input('educations', []))->filter(fn ($row) => filled($row['institution'] ?? null))->values()->all(),
        ]);

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['job_seeker', 'employer'])],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'township_id' => [
                'nullable',
                'integer',
                Rule::exists('townships', 'id')->where(fn ($query) => $query->where('region_id', $request->input('region_id'))),
            ],
            'company_name' => ['required_if:role,employer', 'nullable', 'string', 'max:255'],
            'company_description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'professional_title' => ['nullable', 'string', 'max:160'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'desired_job_title' => ['nullable', 'string', 'max:160'],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract', 'temporary', 'internship'])],
            'work_mode' => ['nullable', Rule::in(['on_site', 'hybrid', 'remote', 'any'])],
            'availability' => ['nullable', Rule::in(['immediately', 'two_weeks', 'one_month', 'not_looking'])],
            'expected_salary_min' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'expected_salary_max' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'skills_text' => ['nullable', 'string', 'max:2000'],
            'languages_text' => ['nullable', 'string', 'max:1000'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'experiences' => ['nullable', 'array', 'max:20'],
            'experiences.*.job_title' => ['required', 'string', 'max:160'],
            'experiences.*.employer_name' => ['required', 'string', 'max:160'],
            'experiences.*.location' => ['nullable', 'string', 'max:160'],
            'experiences.*.started_on' => ['nullable', 'date'],
            'experiences.*.ended_on' => ['nullable', 'date', 'after_or_equal:experiences.*.started_on'],
            'experiences.*.is_current' => ['nullable', 'boolean'],
            'experiences.*.description' => ['nullable', 'string', 'max:5000'],
            'educations' => ['nullable', 'array', 'max:20'],
            'educations.*.institution' => ['required', 'string', 'max:180'],
            'educations.*.qualification' => ['nullable', 'string', 'max:160'],
            'educations.*.field_of_study' => ['nullable', 'string', 'max:160'],
            'educations.*.started_year' => ['nullable', 'integer', 'min:1900', 'max:' . now()->year],
            'educations.*.graduated_year' => ['nullable', 'integer', 'min:1900', 'max:' . (now()->year + 10)],
            'educations.*.description' => ['nullable', 'string', 'max:3000'],
        ]);
        $this->validateSalaryRange($attributes);
        $attributes['experiences'] = $this->normalizeExperiences($attributes['experiences'] ?? []);
        $attributes['educations'] = $this->normalizeEducations($attributes['educations'] ?? []);

        $user = DB::transaction(function () use ($attributes, $request) {
            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'role' => $attributes['role'],
                'status' => true,
            ]);

            $profileLocation = [
                'region_id' => $attributes['region_id'],
                'township_id' => $attributes['township_id'] ?? null,
            ];

            if ($attributes['role'] === 'employer') {
                $user->employer()->create([
                    ...$profileLocation,
                    'company_name' => $attributes['company_name'],
                    'company_description' => $attributes['company_description'] ?? null,
                    'location' => $attributes['location'] ?? null,
                ]);
            } else {
                $jobSeeker = $user->jobSeeker()->create([
                    ...$profileLocation,
                    'phone' => $attributes['phone'] ?? null,
                    'professional_title' => $attributes['professional_title'] ?? null,
                    'years_experience' => $attributes['years_experience'] ?? null,
                    'desired_job_title' => $attributes['desired_job_title'] ?? null,
                    'employment_type' => $attributes['employment_type'] ?? null,
                    'work_mode' => $attributes['work_mode'] ?? null,
                    'availability' => $attributes['availability'] ?? null,
                    'expected_salary_min' => $attributes['expected_salary_min'] ?? null,
                    'expected_salary_max' => $attributes['expected_salary_max'] ?? null,
                    'skills' => $this->parseTags($attributes['skills_text'] ?? ''),
                    'languages' => $this->parseTags($attributes['languages_text'] ?? ''),
                    'bio' => $attributes['bio'] ?? null,
                    'profile_photo_path' => $request->file('profile_photo')?->store('job-seeker-photos', 'public'),
                    'cv_path' => $request->file('cv')?->store('job-seeker-cvs', 'local'),
                    'cv_original_name' => $request->file('cv')?->getClientOriginalName(),
                ]);
                $jobSeeker->experiences()->createMany($attributes['experiences'] ?? []);
                $jobSeeker->educations()->createMany($attributes['educations'] ?? []);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/')->with('status', 'Your account has been created.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'job_seeker' && $user->jobSeeker, 403);

        $request->merge([
            'experiences' => collect($request->input('experiences', []))->filter(fn ($row) => filled($row['job_title'] ?? null) || filled($row['employer_name'] ?? null))->values()->all(),
            'educations' => collect($request->input('educations', []))->filter(fn ($row) => filled($row['institution'] ?? null))->values()->all(),
        ]);

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'township_id' => ['nullable', 'integer', Rule::exists('townships', 'id')->where(fn ($query) => $query->where('region_id', $request->input('region_id')))],
            'phone' => ['nullable', 'string', 'max:40'],
            'professional_title' => ['nullable', 'string', 'max:160'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'desired_job_title' => ['nullable', 'string', 'max:160'],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract', 'temporary', 'internship'])],
            'work_mode' => ['nullable', Rule::in(['on_site', 'hybrid', 'remote', 'any'])],
            'availability' => ['nullable', Rule::in(['immediately', 'two_weeks', 'one_month', 'not_looking'])],
            'expected_salary_min' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'expected_salary_max' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'skills_text' => ['nullable', 'string', 'max:2000'],
            'languages_text' => ['nullable', 'string', 'max:1000'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'experiences' => ['nullable', 'array', 'max:20'],
            'experiences.*.job_title' => ['required', 'string', 'max:160'],
            'experiences.*.employer_name' => ['required', 'string', 'max:160'],
            'experiences.*.location' => ['nullable', 'string', 'max:160'],
            'experiences.*.started_on' => ['nullable', 'date'],
            'experiences.*.ended_on' => ['nullable', 'date', 'after_or_equal:experiences.*.started_on'],
            'experiences.*.is_current' => ['nullable', 'boolean'],
            'experiences.*.description' => ['nullable', 'string', 'max:5000'],
            'educations' => ['nullable', 'array', 'max:20'],
            'educations.*.institution' => ['required', 'string', 'max:180'],
            'educations.*.qualification' => ['nullable', 'string', 'max:160'],
            'educations.*.field_of_study' => ['nullable', 'string', 'max:160'],
            'educations.*.started_year' => ['nullable', 'integer', 'min:1900', 'max:' . now()->year],
            'educations.*.graduated_year' => ['nullable', 'integer', 'min:1900', 'max:' . (now()->year + 10)],
            'educations.*.description' => ['nullable', 'string', 'max:3000'],
        ]);
        $this->validateSalaryRange($attributes);
        $attributes['experiences'] = $this->normalizeExperiences($attributes['experiences'] ?? []);
        $attributes['educations'] = $this->normalizeEducations($attributes['educations'] ?? []);

        if (empty($attributes['password'])) {
            unset($attributes['password']);
        }

        $userAttributes = array_intersect_key($attributes, array_flip(['name', 'email', 'password']));
        $jobSeeker = $user->jobSeeker;
        $profileAttributes = [
            'region_id' => $attributes['region_id'] ?? null,
            'township_id' => $attributes['township_id'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'professional_title' => $attributes['professional_title'] ?? null,
            'years_experience' => $attributes['years_experience'] ?? null,
            'desired_job_title' => $attributes['desired_job_title'] ?? null,
            'employment_type' => $attributes['employment_type'] ?? null,
            'work_mode' => $attributes['work_mode'] ?? null,
            'availability' => $attributes['availability'] ?? null,
            'expected_salary_min' => $attributes['expected_salary_min'] ?? null,
            'expected_salary_max' => $attributes['expected_salary_max'] ?? null,
            'skills' => $this->parseTags($attributes['skills_text'] ?? ''),
            'languages' => $this->parseTags($attributes['languages_text'] ?? ''),
            'bio' => $attributes['bio'] ?? null,
        ];

        if ($request->hasFile('profile_photo')) {
            $profileAttributes['profile_photo_path'] = $request->file('profile_photo')->store('job-seeker-photos', 'public');
        }
        if ($request->hasFile('cv')) {
            $profileAttributes['cv_path'] = $request->file('cv')->store('job-seeker-cvs', 'local');
            $profileAttributes['cv_original_name'] = $request->file('cv')->getClientOriginalName();
        }

        $previousPhoto = $jobSeeker->profile_photo_path;
        $previousResume = $jobSeeker->cv_path;
        DB::transaction(function () use ($user, $userAttributes, $jobSeeker, $profileAttributes, $attributes) {
            $user->updateProfile($userAttributes);
            $jobSeeker->update($profileAttributes);
            $jobSeeker->experiences()->delete();
            $jobSeeker->experiences()->createMany($attributes['experiences'] ?? []);
            $jobSeeker->educations()->delete();
            $jobSeeker->educations()->createMany($attributes['educations'] ?? []);
        });
        if ($request->hasFile('profile_photo') && $previousPhoto) Storage::disk('public')->delete($previousPhoto);
        if ($request->hasFile('cv') && $previousResume) Storage::disk('local')->delete($previousResume);

        return redirect('/profile')->with('status', 'Your profile has been saved.');
    }

    public function downloadResume(Request $request)
    {
        $jobSeeker = $request->user()->jobSeeker;
        abort_unless($request->user()->role === 'job_seeker' && $jobSeeker?->cv_path, 404);
        return Storage::disk('local')->download($jobSeeker->cv_path, basename($jobSeeker->cv_original_name ?: $jobSeeker->cv_path));
    }

    private function appView(?string $authPage = null, string $page = 'home'): View
    {
        $user = Auth::user();
        $seekerProfile = null;
        if ($page === 'profile' && $user?->role === 'job_seeker') {
            $seeker = $user->jobSeeker()->with([
                'region:id,name,type', 'township:id,name', 'experiences', 'educations',
            ])->withCount(['applications', 'savedJobs'])->first();
            if ($seeker) {
                $seekerProfile = [...$seeker->toArray(), 'user' => $user->only(['name', 'email'])];
                $seekerProfile['profile_photo_url'] = $seeker->profile_photo_path
                    ? Storage::disk('public')->url($seeker->profile_photo_path)
                    : null;
            }
        }
        $authBootstrap = [
            'authPage' => $authPage,
            'page' => $page,
            'authenticated' => Auth::check(),
            'role' => Auth::user()?->role,
            'profile' => $seekerProfile,
            'status' => session('status'),
            'csrfToken' => csrf_token(),
            'errors' => session('errors')?->getBag('default')->getMessages() ?? [],
            'old' => session()->getOldInput(),
            'regions' => $authPage === 'register' || in_array($page, ['home', 'search', 'profile'], true)
                ? Region::with('townships:id,region_id,name')->orderBy('sort_order')->get(['id', 'name', 'type'])
                : [],
        ];

        return view('welcome', compact('authBootstrap'));
    }

    private function parseTags(string $value): array
    {
        return collect(preg_split('/[,\n]+/', $value) ?: [])
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique(fn ($tag) => mb_strtolower($tag))
            ->take(50)
            ->values()
            ->all();
    }

    private function validateSalaryRange(array $attributes): void
    {
        if (isset($attributes['expected_salary_min'], $attributes['expected_salary_max'])
            && $attributes['expected_salary_min'] > $attributes['expected_salary_max']) {
            throw ValidationException::withMessages([
                'expected_salary_max' => 'Maximum salary must be greater than or equal to the minimum salary.',
            ]);
        }
    }

    private function normalizeExperiences(array $experiences): array
    {
        return collect($experiences)->values()->map(fn (array $row, int $index) => [
            ...$row,
            'is_current' => (bool) ($row['is_current'] ?? false),
            'ended_on' => ! empty($row['is_current']) ? null : ($row['ended_on'] ?? null),
            'sort_order' => $index,
        ])->all();
    }

    private function normalizeEducations(array $educations): array
    {
        return collect($educations)->values()->map(fn (array $row, int $index) => [...$row, 'sort_order' => $index])->all();
    }
}
