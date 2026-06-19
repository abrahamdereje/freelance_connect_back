<?php

namespace App\Services;

use App\DTOs\RegisterDTO;
use App\DTOs\LoginDTO;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRole;
use App\Models\EmployerProfile;
use App\Models\FreelancerProfile;
use App\Models\AdminProfile;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected WalletRepositoryInterface $walletRepository
    ) {}

    public function register(RegisterDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            // Create user
            $user = $this->userRepository->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'role' => $dto->role,
                'is_suspended' => false,
            ]);

            // Create profile based on role
            switch ($dto->role) {
                case UserRole::EMPLOYER:
                    EmployerProfile::create([
                        'user_id' => $user->id,
                        'rating' => 0.00,
                    ]);
                    break;
                case UserRole::FREELANCER:
                    FreelancerProfile::create([
                        'user_id' => $user->id,
                        'rating' => 0.00,
                        'hourly_rate' => 0.00,
                    ]);
                    break;
                case UserRole::ADMIN:
                    AdminProfile::create([
                        'user_id' => $user->id,
                    ]);
                    break;
            }

            // Create wallet
            $this->walletRepository->createWallet($user->id);

            return $user;
        });
    }

    public function login(LoginDTO $dto): array
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if ($user->is_suspended) {
            throw ValidationException::withMessages([
                'email' => ['Your account is suspended.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
