<?php

namespace Database\Factories;

use App\Models\ReportExport;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportExportFactory extends Factory
{
    protected $model = ReportExport::class;

    public function definition(): array
    {
        return [
            'report_slug'  => 'hospital-list',
            'report_label' => 'Hospital List',
            'category'     => 'hospital',
            'format'       => $this->faker->randomElement(ReportExport::FORMATS),
            'filters'      => null,
            'row_count'    => $this->faker->numberBetween(1, 100),
        ];
    }
}
