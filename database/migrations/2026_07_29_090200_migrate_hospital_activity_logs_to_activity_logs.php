<?php

use App\Models\Hospital;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unifies the two competing audit-trail tables onto the one already used
     * by six other modules (Department, Invoice, Patient, Settings,
     * ShiftHandover, Payment). See SUPER_ADMIN_MASTER_PLAN.md §5.10/§7.8.
     */
    public function up(): void
    {
        if (! Schema::hasTable('hospital_activity_logs')) {
            return;
        }

        $rows = DB::table('hospital_activity_logs')->orderBy('id')->get();

        foreach ($rows as $row) {
            DB::table('activity_logs')->insert([
                'user_id'     => null,
                'user_name'   => 'System',
                'action'      => $row->type,
                'module'      => 'hospital',
                'description' => $row->description,
                'model_type'  => Hospital::class,
                'model_id'    => $row->hospital_id,
                'ip_address'  => null,
                'user_agent'  => null,
                'old_values'  => null,
                'new_values'  => null,
                'created_at'  => $row->created_at,
                'updated_at'  => $row->created_at,
            ]);
        }

        Schema::dropIfExists('hospital_activity_logs');
    }

    public function down(): void
    {
        Schema::create('hospital_activity_logs', function ($table) {
            $table->id();
            $table->foreignId('hospital_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('description');
            $table->timestamp('created_at')->nullable();
        });

        DB::table('activity_logs')->where('module', 'hospital')->orderBy('id')->get()->each(function ($row) {
            DB::table('hospital_activity_logs')->insert([
                'hospital_id' => $row->model_id,
                'type'        => $row->action,
                'description' => $row->description,
                'created_at'  => $row->created_at,
            ]);
        });

        DB::table('activity_logs')->where('module', 'hospital')->delete();
    }
};
