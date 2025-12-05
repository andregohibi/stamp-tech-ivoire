<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Signatory>
 */
class SignatoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'id' => (string)Str::uuid(),
            'company_id' => Company::factory(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'position' => fake()->randomElement([
                'Directeur Général',
                'Directeur Administratif et Financier',
                'Directeur des Ressources Humaines',
                'Directeur Commercial',
                'Directeur des Opérations',
                'Responsable Juridique',
                'Secrétaire Général',
                'Directeur Technique',
            ]),
            'department' => fake()->randomElement([
                'Direction Générale',
                'Direction Administrative et Financière',
                'Direction des Ressources Humaines',
                'Direction Commerciale',
                'Direction des Opérations',
                'Service Juridique',
                'Secrétariat Général',
                'Direction Technique',
            ]),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->randomElement([
                'Cocody, Angré 8ème tranche',
                'Plateau, Avenue Franchet d\'Esperey',
                'Marcory, Zone 4',
                'Yopougon, Sicogi',
                'Treichville, Boulevard de la République',
                'Adjamé, Rue du Commerce',
                'Abobo, Avocatier',
            ]) . ', ' . fake()->streetAddress(),

            'signature_image' => fake()->boolean(30) ? 'signatures/' . fake()->uuid() . '.png' : null,
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'suspended']), // Plus de chance d'être actif
            'can_generate_qr' => fake()->boolean(70), 

            'notes' => fake()->optional(0.4)->paragraph(),

             // User tracking
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
            
            // Timestamps
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fake()->dateTimeBetween('-6 months', 'now'),
    

        ];
    }

     public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'can_generate_qr' => true,
        ]);
    }

     public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'can_generate_qr' => false,
        ]);
    }


    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
            'can_generate_qr' => false,
        ]);
    }

    public function fired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'fired',
            'can_generate_qr' => false,
            'deleted_at' => now(),
            'deleted_by' => User::factory(),
        ]);
    }

    public function withSignature(): static
    {
        return $this->state(fn (array $attributes) => [
            'signature_image' => 'signatures/' . fake()->uuid() . '.png',
        ]);
    }

     public function canGenerateQr(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_generate_qr' => true,
        ]);
    }

       public function director(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => fake()->randomElement([
                'Directeur Général',
                'Directeur Général Adjoint',
                'Directeur Administratif et Financier',
            ]),
            'department' => 'Direction Générale',
            'can_generate_qr' => true,
            'status' => 'active',
        ]);
    }

     public function forCompany(string $companyId): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $companyId,
        ]);
    }


}
