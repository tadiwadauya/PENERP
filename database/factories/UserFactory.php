<?php

namespace Database\Factories;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'employee_number' => 'EMP'.$this->faker->unique()->numerify('#####'),
            'first_name' => $this->faker->firstName(),
            'surname' => $this->faker->lastName(),
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'employment_status' => 'active',
            'account_status' => 'active',
            'is_active' => true,
            'is_system_administrator' => false,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
}