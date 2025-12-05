<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Récupérer un utilisateur aléatoire
        $user = User::inRandomOrder()->first();

        return [
         'id' => (string) Str::uuid(),
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company() . ' ' . $this->faker->companySuffix(),
            'registration_number' => $this->faker->numerify('RC-####-####'),
            'sector' => $this->faker->randomElement([
                'Technology',
                'Finance',
                'Healthcare',
                'Education',
                'Manufacturing',
                'Retail',
                'Services',
                'Construction',
                'Transportation',
                'Agriculture'
            ]),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->companyEmail(),
            'logo' => null,
            'website' => $this->faker->url(),
            'status' => $this->faker->randomElement(['active', 'disabled', 'revoked']),
            'subscription_type' => $this->faker->randomElement(['free', 'basic', 'premium', 'enterprise']),
            'subscription_expires_at' => $this->faker->dateTimeBetween('now', '+2 years'),
            'qr_quota' => $this->faker->numberBetween(10, 1000),
            'qr_used' => $this->faker->numberBetween(0, 50),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => $user ? $user->name : null,
        ];
    }
}
