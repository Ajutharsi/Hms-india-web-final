<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Drug;
use App\Models\LabOrder;
use App\Models\Ward;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function kpis()
    {
        $lowStockThreshold = config('hospital.low_stock', 10);
        $nearExpiryDays    = config('hospital.near_expiry', 30);

        $monthlyPatients = [];
        $monthlyRevenue  = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyPatients[] = [
                'month' => $month->format('M Y'),
                'count' => Patient::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)->count(),
            ];
            $monthlyRevenue[] = [
                'month'  => $month->format('M Y'),
                'amount' => round(Invoice::where('status', 'paid')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('total'), 2),
            ];
        }

        return response()->json([
            'total_patients'       => Patient::count(),
            'active_patients'      => Patient::where('status', 'active')->count(),
            'total_doctors'        => Doctor::count(),
            'total_appointments'   => Appointment::count(),
            'today_appointments'   => Appointment::whereDate('appointment_date', today())->count(),
            'total_revenue'        => round(Invoice::where('status', 'paid')->sum('total'), 2),
            'today_revenue'        => round(Invoice::where('status', 'paid')->whereDate('created_at', today())->sum('total'), 2),
            'outstanding'          => round(Invoice::where('status', 'unpaid')->sum('balance'), 2),
            'pending_bills'        => Invoice::where('status', 'unpaid')->count(),
            'available_beds'       => (int) Ward::sum('available_beds'),
            'total_lab_orders'     => LabOrder::count(),
            'pending_lab_orders'   => LabOrder::where('status', 'pending')->count(),
            'low_stock_drugs'      => Drug::where('current_stock', '<=', $lowStockThreshold)->count(),
            'near_expiry_drugs'    => Drug::whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', now()->addDays($nearExpiryDays))
                ->whereDate('expiry_date', '>=', now())
                ->count(),
            'monthly_patients'     => $monthlyPatients,
            'monthly_revenue'      => $monthlyRevenue,
            'patient_type_chart'   => [
                'op' => Patient::where('patient_type', 'op')->count(),
                'ip' => Patient::where('patient_type', 'ip')->count(),
            ],
            'appointment_status_chart' => [
                'scheduled' => Appointment::where('status', 'scheduled')->count(),
                'completed' => Appointment::where('status', 'completed')->count(),
                'cancelled' => Appointment::where('status', 'cancelled')->count(),
                'no_show'   => Appointment::where('status', 'no_show')->count(),
            ],
        ]);
    }

    protected function filteredPatients()
    {
        return Patient::query()
            ->when(request('from_date'), fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when(request('to_date'), fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when(request('patient_type'), fn ($q, $v) => $q->where('patient_type', $v))
            ->when(request('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->get();
    }

    public function patients()
    {
        $patients = $this->filteredPatients();
        return view('reports.patient', compact('patients'));
    }

    public function patientsPdf()
    {
        $patients = $this->filteredPatients();
        return view('reports.patient', compact('patients'));
    }

    protected function filteredInvoices()
    {
        return Invoice::with('patient')
            ->when(request('from_date'), fn ($q, $v) => $q->whereDate('invoice_date', '>=', $v))
            ->when(request('to_date'), fn ($q, $v) => $q->whereDate('invoice_date', '<=', $v))
            ->when(request('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->get();
    }

    public function billing()
    {
        $invoices     = $this->filteredInvoices();
        $totalAmount  = $invoices->sum('total');
        $totalPaid    = $invoices->sum('paid_amount');
        $totalBalance = $invoices->sum('balance');

        return view('reports.billing', compact('invoices', 'totalAmount', 'totalPaid', 'totalBalance'));
    }

    public function billingPdf()
    {
        $invoices     = $this->filteredInvoices();
        $totalAmount  = $invoices->sum('total');
        $totalPaid    = $invoices->sum('paid_amount');
        $totalBalance = $invoices->sum('balance');

        return view('reports.billing', compact('invoices', 'totalAmount', 'totalPaid', 'totalBalance'));
    }

    protected function filteredDrugs()
    {
        $nearExpiryDays = config('hospital.near_expiry', 30);

        return Drug::query()
            ->when(request('filter') === 'low_stock', fn ($q) => $q->whereColumn('current_stock', '<=', 'min_stock_level'))
            ->when(request('filter') === 'expiry', fn ($q) => $q->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', now()->addDays($nearExpiryDays))
                ->whereDate('expiry_date', '>=', now()))
            ->when(request('filter') === 'expired', fn ($q) => $q->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', now()))
            ->latest()
            ->get();
    }

    public function inventory()
    {
        $drugs = $this->filteredDrugs();
        return view('reports.inventory', compact('drugs'));
    }

    public function inventoryPdf()
    {
        $drugs = $this->filteredDrugs();
        return view('reports.inventory', compact('drugs'));
    }
}