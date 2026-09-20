<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Drug;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\RoundAssignment;
use App\Models\Ward;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class DashboardService
{
    private const CACHE_TTL = 60; // seconds

   
    private function nearExpiryDrugsQuery()
    {
        return Drug::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(config('hospital.near_expiry', 30)))
            ->whereDate('expiry_date', '>=', now())
            ->where('status', 'active');
    }

    public function nearExpiryDrugsCount(): int
    {
        return Cache::remember('dashboard.near_expiry_drugs_count', self::CACHE_TTL, fn() =>
            $this->nearExpiryDrugsQuery()->count()
        );
    }

    /**
     * Active doctors minus whoever has an approved leave request covering
     * today. Doctor links to User by email (see Doctor::user()), not
     * user_id, so the leave lookup joins on that.
     */
    private function availableDoctorsTodayCount(): int
    {
        $onLeaveEmails = DB::table('leave_requests')
            ->join('users', 'users.id', '=', 'leave_requests.user_id')
            ->where('leave_requests.status', 'approved')
            ->whereDate('leave_requests.from_date', '<=', today())
            ->whereDate('leave_requests.to_date', '>=', today())
            ->pluck('users.email');

        return Doctor::active()->whereNotIn('email', $onLeaveEmails)->count();
    }

    /** @return array<string, int|float> */
    public function coreCounts(): array
    {
        return Cache::remember('dashboard.core_counts', self::CACHE_TTL, fn() => [
            'totalPatients'     => Patient::count(),
            'activePatients'    => Patient::where('status', 'active')->count(),
            'totalDoctors'      => Doctor::count(),
            'todayAppointments' => Appointment::whereDate('appointment_date', today())->count(),
            'totalRevenue'      => (float) Invoice::where('status', 'paid')->sum('total'),
            'pendingBills'      => Invoice::where('status', 'unpaid')->count(),
            'lowStockDrugs'     => Drug::where('current_stock', '<=', config('hospital.low_stock', 10))->count(),
            'availableBeds'     => (int) Ward::sum('available_beds'),
        ]);
    }

    /** @return array<int, array{type: string, icon: string, msg: string}> */
    public function systemAlerts(): array
    {
        return Cache::remember('dashboard.system_alerts', self::CACHE_TTL, function () {
            $threshold = config('hospital.low_stock', 10);

            // Each count computed exactly once.
            $lowStock      = Drug::where('current_stock', '<=', $threshold)->count();
            $nearExpiry    = $this->nearExpiryDrugsCount();
            $unpaid        = Invoice::where('status', 'unpaid')->count();
            $overdueRounds = RoundAssignment::where('status', 'active')->where('next_round_at', '<=', now())->count();
            $pendingLabs   = LabOrder::where('status', 'pending')->count();

           
            $alerts = [];
            if ($lowStock > 0)      $alerts[] = ['type' => 'danger',  'icon' => 'tabler-pill',         'msg' => "{$lowStock} drugs below stock threshold", 'url' => route('pharmacy.drugs.index')];
            if ($nearExpiry > 0)    $alerts[] = ['type' => 'warning', 'icon' => 'tabler-calendar-x',   'msg' => "{$nearExpiry} drugs near expiry", 'url' => route('pharmacy.drugs.index')];
            if ($unpaid > 0)        $alerts[] = ['type' => 'warning', 'icon' => 'tabler-file-invoice', 'msg' => "{$unpaid} unpaid invoices outstanding", 'url' => route('billing.invoices.index')];
            if ($overdueRounds > 0) $alerts[] = ['type' => 'danger',  'icon' => 'tabler-clock',        'msg' => "{$overdueRounds} overdue staff rounds", 'url' => route('staff-rounds.index')];
            if ($pendingLabs > 0)   $alerts[] = ['type' => 'info',    'icon' => 'tabler-flask',        'msg' => "{$pendingLabs} lab orders pending", 'url' => route('lab.orders.index')];

            return $alerts;
        });
    }

    /**
     * Six-month patient registration + paid-revenue trend.
     * Two grouped queries instead of the previous twelve.
     *
     * @return array{patients: array, revenue: array}
     */
    public function monthlyTrends(): array
    {
        return Cache::remember('dashboard.monthly_trends', self::CACHE_TTL * 5, function () {
            $from = now()->subMonths(5)->startOfMonth();

            $patientCounts = Patient::where('created_at', '>=', $from)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')->pluck('cnt', 'ym');

            $revenueSums = Invoice::where('status', 'paid')
                ->where('created_at', '>=', $from)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(total) as amt")
                ->groupBy('ym')->pluck('amt', 'ym');

            $patients = [];
            $revenue  = [];
            for ($i = 5; $i >= 0; $i--) {
             
                $month = now()->startOfMonth()->subMonths($i);
                $ym    = $month->format('Y-m');
                $patients[] = ['month' => $month->format('M'), 'count'  => (int) ($patientCounts[$ym] ?? 0)];
                $revenue[]  = ['month' => $month->format('M'), 'amount' => (float) ($revenueSums[$ym] ?? 0)];
            }

            return ['patients' => $patients, 'revenue' => $revenue];
        });
    }

    public function todayAppointmentsList(?int $doctorId = null, ?string $doctorName = null)
    {
        return Appointment::with(['patient:id,name,mrn', 'doctor:id,name'])
            ->whereDate('appointment_date', today())
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->latest()->take(5)->get()
            ->map(fn($a) => [
                'id'     => $a->id,
                'name'   => $a->patient->name ?? 'N/A',
                'doctor' => 'Dr. ' . ($doctorName ?? $a->doctor->name ?? 'N/A'),
                'time'   => \Carbon\Carbon::parse($a->appointment_time)->format('h:i A'),
                'type'   => strtoupper($a->type),
                'color'  => $a->type === 'op' ? 'primary' : 'danger',
            ]);
    }

    /** Latest registered patients, mapped for the dashboard widget. */
    public function recentPatients(?int $doctorId = null, ?string $patientType = null)
    {
        return Patient::query()
            ->when($doctorId, fn($q) => $q->whereHas('appointments', fn($aq) => $aq->where('doctor_id', $doctorId)))
            ->when($patientType, fn($q) => $q->where('patient_type', $patientType)->where('status', 'active'))
            ->latest()->take(5)->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'mrn'       => $p->mrn,
                'status'    => $p->status,
                'photo_url' => $p->photo_url,
            ]);
    }

   
    public function detailStats(): array
    {
        return Cache::remember('dashboard.detail_stats', self::CACHE_TTL, function () {
            $threshold = config('hospital.low_stock', 10);

            // One grouped query per dimension instead of a COUNT per status.
            $pStatus = Patient::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
            $pType   = Patient::selectRaw('patient_type, COUNT(*) as c')->groupBy('patient_type')->pluck('c', 'patient_type');
            $aToday  = Appointment::whereDate('appointment_date', today())
                ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
            $aTypeToday = Appointment::whereDate('appointment_date', today())
                ->selectRaw('type, COUNT(*) as c')->groupBy('type')->pluck('c', 'type');
            $invAgg = Invoice::selectRaw("status, COUNT(*) as c, SUM(total) as t, SUM(balance) as b")
                ->groupBy('status')->get()->keyBy('status');

            return [
                'patients' => [
                    'total'      => (int) $pStatus->sum(),
                    'active'     => (int) ($pStatus['active'] ?? 0),
                    'discharged' => (int) ($pStatus['discharged'] ?? 0),
                    'deceased'   => (int) ($pStatus['deceased'] ?? 0),
                    'ip'         => (int) ($pType['ip'] ?? 0),
                    'op'         => (int) ($pType['op'] ?? 0),
                    'today'      => Patient::whereDate('created_at', today())->count(),
                    'month'      => Patient::whereMonth('created_at', now()->month)->count(),
                    'activeIp'   => Patient::active()->where('patient_type', 'ip')->count(),
                    'activeOp'   => Patient::active()->where('patient_type', 'op')->count(),
                    'recent'     => Patient::active()->latest()->take(5)->get(['id', 'name', 'mrn', 'status']),
                ],
                'appointments' => [
                    'scheduledToday' => (int) ($aToday['scheduled'] ?? 0),
                    'completedToday' => (int) ($aToday['completed'] ?? 0),
                    'cancelledToday' => (int) ($aToday['cancelled'] ?? 0),
                    'opToday'        => (int) ($aTypeToday['op'] ?? 0),
                    'emergencyToday' => (int) ($aTypeToday['emergency'] ?? 0),
                    'total'          => Appointment::count(),
                    'topDoctors'     => Appointment::with('doctor:id,name,specialization,photo')
                        ->whereMonth('appointment_date', now()->month)
                        ->whereYear('appointment_date', now()->year)
                        ->select('doctor_id', DB::raw('count(*) as total'))
                        ->groupBy('doctor_id')->orderByDesc('total')->take(5)->get(),
                ],
                'wards' => [
                    'total'         => Ward::count(),
                    'totalBeds'     => (int) Ward::sum('total_beds'),
                    'availableBeds' => (int) Ward::sum('available_beds'),
                    'list'          => Ward::take(5)->get(),
                ],
                'doctors' => [
                    'active'         => Doctor::active()->count(),
                    'inactive'       => Doctor::where('status', 'inactive')->count(),
                    'availableToday' => $this->availableDoctorsTodayCount(),
                    'recent'         => Doctor::latest()->take(5)->get(),
                ],
                'revenue' => [
                    'total'       => (float) ($invAgg['paid']->t ?? 0),
                    'today'       => (float) Invoice::where('status', 'paid')->whereDate('created_at', today())->sum('total'),
                    'month'       => (float) Invoice::where('status', 'paid')->whereMonth('created_at', now()->month)->sum('total'),
                    'outstanding' => (float) ($invAgg['unpaid']->b ?? 0),
                    'invoices'    => (int) $invAgg->sum('c'),
                    'paidCount'   => (int) ($invAgg['paid']->c ?? 0),
                    'unpaidCount' => (int) ($invAgg['unpaid']->c ?? 0),
                    'unpaidTotal' => (float) ($invAgg['unpaid']->t ?? 0),
                    'pendingList' => Invoice::with('patient:id,name,mrn')->where('status', 'unpaid')->latest()->take(5)->get(),
                ],
                'drugs' => [
                    'total'      => Drug::count(),
                    'lowStock'   => Drug::where('current_stock', '<=', $threshold)->get(),
                    'nearExpiry' => $this->nearExpiryDrugsQuery()->get(),
                ],
                'admissions' => [
                    'total'  => Admission::count(),
                    'active' => Admission::where('status', 'admitted')->count(),
                    'recent' => Admission::with('patient:id,name,mrn')->where('status', 'admitted')->latest('admission_date')->take(5)->get(),
                ],
                'labs' => [
                    'total'   => LabOrder::count(),
                    'pending' => LabOrder::where('status', 'pending')->count(),
                    'recent'  => LabOrder::with('patient:id,name,mrn')->where('status', 'pending')->latest()->take(5)->get(),
                ],
                'bloodBank' => (function () {
                    $byGroup = \App\Models\BloodUnit::where('status', 'available')
                        ->selectRaw('blood_group, COUNT(*) as c')->groupBy('blood_group')->pluck('c', 'blood_group');

                    return [
                        'available'  => (int) $byGroup->sum(),
                        'byGroup'    => $byGroup,
                        'lowGroups'  => collect(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])
                            ->filter(fn($g) => ($byGroup[$g] ?? 0) < 5)->count(),
                        'nearExpiry' => \App\Models\BloodUnit::where('status', 'available')
                            ->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now()->addDays(7))->count(),
                    ];
                })(),
                'pending' => [
                    'consentForms' => \App\Models\ConsentForm::where('status', 'pending')->count(),
                    'referrals'    => \App\Models\Referral::where('status', 'pending')->count(),
                    'labOrders'    => LabOrder::where('status', 'pending')->count(),
                    'invoices'     => (int) ($invAgg['unpaid']->c ?? 0),
                    'rounds'       => RoundAssignment::where('status', 'active')->where('next_round_at', '<=', now())->count(),
                ],
               
                'staffCount' => \App\Models\User::role(['nurse', 'staff', 'doctor', 'receptionist', 'lab_assistant', 'pharmacist'])->count(),
            ];
        });
    }

    /** MySQL-only DB size lookup for the super-admin health card. */
    public function systemHealth(): array
    {
        $dbSize = Cache::remember('dashboard.db_size', self::CACHE_TTL * 5, function () {
            try {
                return DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = DATABASE()")[0]->size ?? 0;
            } catch (\Throwable) {
                return 0; // non-MySQL driver (e.g. sqlite in tests)
            }
        });

        return [
            'db_size'         => $dbSize . ' MB',
            'total_users'     => \App\Models\User::count(),
            'php_version'     => phpversion(),
            'laravel_version' => app()->version(),
            'server_time'     => now()->format('d M Y H:i'),
        ];
    }

   
    public function platformStats(): array
    {
        return Cache::remember('dashboard.platform_stats', self::CACHE_TTL, function () {
            $byStatus = \App\Models\Hospital::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

            return [
                'total'           => (int) $byStatus->sum(),
                'active'          => (int) ($byStatus['active'] ?? 0),
                'trial'           => (int) ($byStatus['trial'] ?? 0),
                'suspended'       => (int) ($byStatus['suspended'] ?? 0),
                // Soft-deleted, not a `status` enum value — see Hospital Management report.
                'archived'        => \App\Models\Hospital::onlyTrashed()->count(),
                'expiringSoon'    => \App\Models\Hospital::expiringWithin(30)->count(),
                'monthlyRevenue'  => (float) \App\Models\Hospital::where('status', 'active')->sum('monthly_fee'),
                'platformUsers'   => \App\Models\User::count(),
            ];
        });
    }

    /** New-hospital-registrations-per-month, last 6 months — one grouped query. */
    public function hospitalGrowthTrend(): array
    {
        return Cache::remember('dashboard.hospital_growth_trend', self::CACHE_TTL * 5, function () {
            $from = now()->subMonths(5)->startOfMonth();

            $counts = \App\Models\Hospital::where('created_at', '>=', $from)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')->pluck('cnt', 'ym');

            $trend = [];
            for ($i = 5; $i >= 0; $i--) {
                // See the comment in monthlyTrends() above — anchoring to day 1
                // avoids subMonths() overflowing into the wrong month.
                $month = now()->startOfMonth()->subMonths($i);
                $ym    = $month->format('Y-m');
                $trend[] = ['month' => $month->format('M'), 'count' => (int) ($counts[$ym] ?? 0)];
            }

            return $trend;
        });
    }

    /**
     * Computed platform-owner alerts (subscriptions/trials ending soon,
     * suspended tenants) for the dashboard's Notifications panel. Derived on
     * the fly from Hospital data rather than a persisted notifications table
     * — the full Laravel-notifications system is Phase 3 scope.
     *
     * @return array<int, array{level: string, icon: string, message: string, url: string}>
     */
    public function platformNotifications(): array
    {
        return Cache::remember('dashboard.platform_notifications', self::CACHE_TTL, function () {
            $notifications = [];

            $urgent = \App\Models\Hospital::whereNotNull('subscription_ends_at')
                ->whereBetween('subscription_ends_at', [now(), now()->addDays(7)])
                ->orderBy('subscription_ends_at')->get(['id', 'name', 'subscription_ends_at']);
            foreach ($urgent as $h) {
                $notifications[] = [
                    'level'   => 'danger',
                    'icon'    => 'tabler-alert-triangle',
                    'message' => "{$h->name}'s subscription expires " . $h->subscription_ends_at->diffForHumans(),
                    'url'     => route('super-admin.hospitals.show', $h->id),
                ];
            }

            $soon = \App\Models\Hospital::whereNotNull('subscription_ends_at')
                ->whereBetween('subscription_ends_at', [now()->addDays(8), now()->addDays(30)])
                ->orderBy('subscription_ends_at')->get(['id', 'name', 'subscription_ends_at']);
            foreach ($soon as $h) {
                $notifications[] = [
                    'level'   => 'warning',
                    'icon'    => 'tabler-calendar-due',
                    'message' => "{$h->name}'s subscription expires " . $h->subscription_ends_at->diffForHumans(),
                    'url'     => route('super-admin.hospitals.show', $h->id),
                ];
            }

            $trialsEnding = \App\Models\Hospital::where('status', 'trial')
                ->whereNotNull('trial_ends_at')
                ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
                ->orderBy('trial_ends_at')->get(['id', 'name', 'trial_ends_at']);
            foreach ($trialsEnding as $h) {
                $notifications[] = [
                    'level'   => 'warning',
                    'icon'    => 'tabler-clock-hour-4',
                    'message' => "{$h->name}'s trial ends " . $h->trial_ends_at->diffForHumans(),
                    'url'     => route('super-admin.hospitals.show', $h->id),
                ];
            }

            $suspended = \App\Models\Hospital::where('status', 'suspended')->count();
            if ($suspended > 0) {
                $notifications[] = [
                    'level'   => 'danger',
                    'icon'    => 'tabler-ban',
                    'message' => "{$suspended} hospital" . ($suspended === 1 ? '' : 's') . ' currently suspended',
                    'url'     => route('super-admin.hospitals.index', ['status' => 'suspended']),
                ];
            }

            return $notifications;
        });
    }

    /** User counts per role for the super-admin dashboard. */
    public function usersByRole(): array
    {
        return Cache::remember('dashboard.users_by_role', self::CACHE_TTL, function () {
            $roles = [
                ['super_admin', 'Super Admin', 'danger'],
                ['admin', 'Admin', 'warning'],
                ['doctor', 'Doctor', 'primary'],
                ['nurse', 'Nurse', 'info'],
                ['receptionist', 'Receptionist', 'success'],
                ['lab_assistant', 'Lab Assistant', 'warning'],
                ['pharmacist', 'Pharmacist', 'danger'],
                ['staff', 'Staff', 'secondary'],
                ['patient', 'Patient', 'success'],
            ];

            // One grouped query instead of nine.
            $counts = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->selectRaw('roles.name as role, COUNT(*) as cnt')
                ->groupBy('roles.name')
                ->pluck('cnt', 'role');

            return collect($roles)->map(fn($r) => [
                'role'  => $r[1],
                'count' => (int) ($counts[$r[0]] ?? 0),
                'color' => $r[2],
            ])->all();
        });
    }
}
