<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\ApiController;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Http\Resources\UserResource;
use App\DTOs\RegisterDTO;
use App\DTOs\LoginDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(protected AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterDTO::fromArray($request->validated());
        $user = $this->authService->register($dto);

        return $this->successResponse(
            new UserResource($user),
            'Registration successful.',
            201
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginDTO::fromArray($request->validated());
        $data = $this->authService->login($dto);

        return $this->successResponse([
            'user' => new UserResource($data['user']),
            'token' => $data['token'],
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(new UserResource($request->user()->load(['employerProfile', 'freelancerProfile', 'adminProfile'])));
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return $this->errorResponse('Email already verified.', 400);
        }

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return $this->successResponse(
            new UserResource($user->fresh(['employerProfile', 'freelancerProfile.skills', 'adminProfile'])),
            'Email verified successfully.'
        );
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($user->isFreelancer()) {
            $rules = array_merge($rules, [
                'hourly_rate' => ['required', 'numeric', 'min:0'],
                'bio' => ['required', 'string'],
                'portfolio_url' => ['nullable', 'url'],
                'skills' => ['nullable', 'string'],
            ]);
        } elseif ($user->isEmployer()) {
            $rules = array_merge($rules, [
                'company_name' => ['nullable', 'string', 'max:255'],
                'company_description' => ['nullable', 'string'],
            ]);
        }

        $validated = $request->validate($rules);

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $validated) {
            $user->update([
                'name' => $validated['name'],
            ]);

            if (!empty($validated['password'])) {
                $user->update([
                    'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
                ]);
            }

            if ($user->isFreelancer()) {
                $profile = $user->freelancerProfile ?: \App\Models\FreelancerProfile::create(['user_id' => $user->id]);
                
                $portfolio = $profile->portfolio ?? [];
                if (isset($validated['portfolio_url'])) {
                    $portfolio['url'] = $validated['portfolio_url'];
                }
                if (isset($validated['experience_years'])) {
                    $portfolio['experience_years'] = (int) $validated['experience_years'];
                }
                
                $profile->update([
                    'hourly_rate' => $validated['hourly_rate'],
                    'bio' => $validated['bio'],
                    'portfolio' => $portfolio,
                ]);

                if (isset($validated['skills'])) {
                    $skillNames = array_filter(array_map('trim', explode(',', $validated['skills'])));
                    $skillIds = [];
                    foreach ($skillNames as $name) {
                        $skill = \App\Models\Skill::firstOrCreate(
                            ['name' => $name],
                            ['slug' => \Illuminate\Support\Str::slug($name)]
                        );
                        $skillIds[] = $skill->id;
                    }
                    $profile->skills()->sync($skillIds);
                } else {
                    $profile->skills()->detach();
                }
            } elseif ($user->isEmployer()) {
                $profile = $user->employerProfile ?: \App\Models\EmployerProfile::create(['user_id' => $user->id]);
                $profile->update([
                    'company_name' => $validated['company_name'] ?? null,
                    'bio' => $validated['company_description'] ?? null,
                ]);
            }
        });

        return $this->successResponse(
            new UserResource($user->fresh(['employerProfile', 'freelancerProfile.skills', 'adminProfile'])),
            'Profile updated successfully.'
        );
    }
}
