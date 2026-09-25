<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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
        ]);

        $user = DB::transaction(function () use ($attributes) {
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
                ]);
            } else {
                $user->jobSeeker()->create($profileLocation);
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
        $attributes = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ]);

        if (empty($attributes['password'])) {
            unset($attributes['password']);
        }

        $user->updateProfile($attributes);
        return back()->with('status', 'Profile updated.');
    }

    private function appView(?string $authPage = null, string $page = 'home'): View
    {
        $authBootstrap = [
            'authPage' => $authPage,
            'page' => $page,
            'authenticated' => Auth::check(),
            'csrfToken' => csrf_token(),
            'errors' => session('errors')?->getBag('default')->getMessages() ?? [],
            'old' => session()->getOldInput(),
            'regions' => $authPage === 'register' || in_array($page, ['home', 'search'], true)
                ? Region::with('townships:id,region_id,name')->orderBy('sort_order')->get(['id', 'name', 'type'])
                : [],
        ];

        return view('welcome', compact('authBootstrap'));
    }
}
