<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JobSearchController;
use App\Http\Controllers\ConversationController;

Route::get('/', [AuthController::class, 'home'])->name('home');
Route::get('/jobs', [AuthController::class, 'searchPage'])->name('jobs.page');
Route::get('/api/jobs/search', [JobSearchController::class, 'index'])->name('jobs.search');

Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->middleware('guest')->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest')->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::patch('/profile', [AuthController::class, 'updateProfile'])->middleware('auth')->name('profile.update');

Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('/applications/{application}/conversation', [ConversationController::class, 'startForApplication'])->middleware('throttle:20,1')->name('conversations.start');
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages'])->name('conversations.messages');
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'storeMessage'])->middleware('throttle:30,1')->name('conversations.messages.store');
    Route::patch('/conversations/{conversation}/read', [ConversationController::class, 'markRead'])->name('conversations.read');
    Route::get('/message-attachments/{attachment}', [ConversationController::class, 'attachment'])->name('message-attachments.show');
});
