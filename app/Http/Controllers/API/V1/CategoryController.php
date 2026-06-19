<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Models\JobCategory;
use Illuminate\Http\JsonResponse;

class CategoryController extends ApiController
{
    public function index(): JsonResponse
    {
        $categories = JobCategory::orderBy('name')->get();

        return $this->successResponse($categories, 'Job categories retrieved successfully.');
    }
}
