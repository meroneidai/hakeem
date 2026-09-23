<?php

namespace Database\Factories;

use App\Enums\CareDocumentType;
use App\Models\CareDocument;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CareDocument>
 */
class CareDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => User::factory(),
            'clinic_id' => Clinic::factory(),
            'type' => CareDocumentType::Consultation,
            'title' => 'استشارة طبية',
            'body' => 'ملاحظات الزيارة.',
            'payload' => [],
            'verification_code' => strtoupper(Str::random(8)),
            'issued_at' => now(),
        ];
    }

    public function prescription(): static
    {
        return $this->state(fn () => [
            'type' => CareDocumentType::Prescription,
            'title' => 'روشتة طبية',
            'payload' => [
                'medications' => [
                    [
                        'name' => 'باراسيتامول',
                        'dose' => '500 مجم',
                        'frequency' => 'ثلاث مرات يوميًا',
                        'duration' => '5 أيام',
                    ],
                ],
            ],
        ]);
    }
}
