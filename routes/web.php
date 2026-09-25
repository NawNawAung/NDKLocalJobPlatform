<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JobSearchController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\EmployerWorkspaceController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\NotificationController;

Route::get('/', [AuthController::class, 'home'])->name('home');
Route::get('/jobs', [AuthController::class, 'searchPage'])->name('jobs.page');
Route::get('/profile', [AuthController::class, 'profilePage'])->middleware('auth')->name('profile.page');
Route::get('/api/jobs/search', [JobSearchController::class, 'index'])->name('jobs.search');
Route::get('/api/jobs/{jobId}', [JobApplicationController::class, 'show'])->whereNumber('jobId')->name('jobs.show');

Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->middleware('guest')->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest')->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::patch('/profile', [AuthController::class, 'updateProfile'])->middleware('auth')->name('profile.update');
Route::get('/profile/resume', [AuthController::class, 'downloadResume'])->middleware('auth')->name('profile.resume');

Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::post('/jobs/{jobId}/applications', [JobApplicationController::class, 'apply'])->whereNumber('jobId')->middleware('throttle:10,1')->name('applications.store');
    Route::post('/jobs/{jobId}/save', [JobApplicationController::class, 'save'])->whereNumber('jobId')->name('jobs.save');
    Route::delete('/jobs/{jobId}/save', [JobApplicationController::class, 'unsave'])->whereNumber('jobId')->name('jobs.unsave');
    Route::get('/employer/dashboard', [EmployerWorkspaceController::class, 'dashboard'])->name('employer.dashboard');
    Route::patch('/employer/profile', [EmployerWorkspaceController::class, 'updateProfile'])->name('employer.profile.update');
    Route::get('/employer/candidates', [EmployerWorkspaceController::class, 'candidates'])->name('employer.candidates');
    Route::post('/employer/jobs', [EmployerWorkspaceController::class, 'createJob'])->name('employer.jobs.store');
    Route::patch('/employer/jobs/{job}', [EmployerWorkspaceController::class, 'updateJob'])->name('employer.jobs.update');
    Route::delete('/employer/jobs/{job}', [EmployerWorkspaceController::class, 'deleteJob'])->name('employer.jobs.destroy');
    Route::patch('/employer/applications/{application}', [EmployerWorkspaceController::class, 'updateApplication'])->name('employer.applications.update');
    Route::get('/employer/applications/{application}/resume', [EmployerWorkspaceController::class, 'downloadResume'])->name('employer.applications.resume');
    Route::post('/employer/applications/{application}/interviews', [EmployerWorkspaceController::class, 'scheduleInterview'])->name('employer.applications.interviews.store');
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('/applications/{application}/conversation', [ConversationController::class, 'startForApplication'])->middleware('throttle:20,1')->name('conversations.start');
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages'])->name('conversations.messages');
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'storeMessage'])->middleware('throttle:30,1')->name('conversations.messages.store');
    Route::patch('/conversations/{conversation}/read', [ConversationController::class, 'markRead'])->name('conversations.read');
    Route::get('/message-attachments/{attachment}', [ConversationController::class, 'attachment'])->name('message-attachments.show');
});
