<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JobSearchController;

Route::get('/', [AuthController::class, 'home'])->name('home');
Route::get('/jobs', [AuthController::class, 'searchPage'])->name('jobs.page');
Route::get('/api/jobs/search', [JobSearchController::class, 'index'])->name('jobs.search');

Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->middleware('guest')->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest')->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::patch('/profile', [AuthController::class, 'updateProfile'])->middleware('auth')->name('profile.update');
