<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;

class SkillController extends ApiController
{
    public function index(): JsonResponse
    {
        $skills = Skill::orderBy('name')->get();

        return $this->successResponse($skills, 'Skills retrieved successfully.');
    }
}
