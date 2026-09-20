<?php

use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Minimal, additive link needed for "Login as Hospital Admin"
     * impersonation to mean something concrete — NOT full multi-tenancy.
     * This does not scope Patients/Doctors/Invoices/etc. to a hospital and
     * adds no data-isolation enforcement; it only records which hospital an
     * admin-role user administers, so impersonation has a real target to
     * act on. See SUPER_ADMIN_IMPERSONATION_REPORT.md §0 for the full
     * reasoning and boundary of this decision.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('hospital_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Every existing admin-role user administers the platform's default
        // hospital in today's single-tenant reality — link them so the
        // feature is immediately meaningful for the one hospital that has
        // real data, consistent with the pattern established in
        // SUPER_ADMIN_HOSPITAL_MANAGEMENT_REPORT.md (real data for the
        // default hospital, no fabrication for others).
        //
        // Guarded: roles are seeded by RolesAndPermissionsSeeder, not a
        // migration, so on a fresh install this migration can run before
        // the 'admin' role (or any users) exist at all — Spatie's role()
        // scope throws RoleDoesNotExist in that case rather than just
        // matching nothing.
        try {
            $defaultHospital = Hospital::default()->first();
            if ($defaultHospital && \Spatie\Permission\Models\Role::where('name', 'admin')->exists()) {
                User::role('admin')->whereNull('hospital_id')->get()->each(function (User $user) use ($defaultHospital) {
                    $user->forceFill(['hospital_id' => $defaultHospital->id])->saveQuietly();
                });
            }
        } catch (\Throwable) {
            // Best-effort backfill — never block the migration on it.
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hospital_id');
        });
    }
};
