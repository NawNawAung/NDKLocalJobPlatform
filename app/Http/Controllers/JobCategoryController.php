<?php

namespace App\Http\Controllers;

use App\Models\JobCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['categories' => JobCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug'])]);
    }
}
