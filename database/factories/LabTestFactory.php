<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LabTestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'test_name'           => $this->faker->unique()->randomElement([
                'Complete Blood Count', 'Blood Sugar Fasting', 'Blood Sugar PP',
                'Lipid Profile', 'Liver Function Test', 'Kidney Function Test',
                'Thyroid Function Test', 'Urine Analysis', 'HbA1c',
                'ECG', 'X-Ray Chest', 'Ultrasound Abdomen',
                'Blood Urea', 'Serum Creatinine', 'SGPT', 'SGOT',
                'Bilirubin Total', 'Hemoglobin', 'Platelet Count', 'ESR'
            ]),
            'test_code'           => 'LT-' . $this->faker->unique()->numerify('####'),
            'category'            => $this->faker->randomElement(['Hematology', 'Biochemistry', 'Microbiology', 'Radiology', 'Pathology']),
            'sample_type'         => $this->faker->randomElement(['Blood', 'Urine', 'Stool', 'Sputum', 'Swab']),
            'price'               => $this->faker->randomElement([200, 300, 500, 750, 1000, 1500, 2000]),
            'unit'                => $this->faker->randomElement(['mg/dL', 'g/dL', 'U/L', 'IU/L', 'mmol/L', '%', 'cells/μL']),
            'normal_range_male'   => $this->faker->numerify('##-###'),
            'normal_range_female' => $this->faker->numerify('##-###'),
            'description'         => $this->faker->sentence(),
            'status'              => 'active',
        ];
    }
}