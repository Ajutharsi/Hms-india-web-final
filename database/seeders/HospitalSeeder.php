<?php

namespace Database\Seeders;

use App\Models\Hospital;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        if (Hospital::count() > 0) {
            return;
        }

        $hospitals = Hospital::factory(24)->create();

        $typeByStatus = [
            'trial'     => ['trial_started', 'started a free trial'],
            'active'    => ['activated', 'subscription activated'],
            'suspended' => ['suspended', 'subscription suspended for non-payment'],
            'cancelled' => ['cancelled', 'cancelled their subscription'],
        ];

        foreach ($hospitals as $hospital) {
            [$type, $verb] = $typeByStatus[$hospital->status];

            // Inserted directly (not via ActivityLog::log()) to preserve
            // realistic backdated timestamps for seeded demo data.
            $this->recordActivity($hospital, 'registered', "{$hospital->name} registered on the platform", $hospital->created_at);

            if ($type !== 'registered') {
                $this->recordActivity($hospital, $type, "{$hospital->name} {$verb}", $hospital->created_at->copy()->addDays(random_int(1, 5)));
            }
        }
    }

    private function recordActivity(Hospital $hospital, string $action, string $description, $createdAt): void
    {
        DB::table('activity_logs')->insert([
            'user_id'     => null,
            'user_name'   => 'System',
            'action'      => $action,
            'module'      => 'hospital',
            'description' => $description,
            'model_type'  => Hospital::class,
            'model_id'    => $hospital->id,
            'ip_address'  => null,
            'user_agent'  => null,
            'old_values'  => null,
            'new_values'  => null,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ]);
    }
}
