<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Plan;
use App\Services\Reports\ReportCatalog;
use App\Services\Reports\ReportCentreService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Super Admin Reports & Export Centre — see SUPER_ADMIN_REPORTS_CENTRE_REPORT.md.
 * Follows the exact DataTables-list pattern already established by
 * SupportTicketController/HospitalController, and the exact CSV-streaming /
 * print-as-PDF export convention already established by AnalyticsController
 * and ReportController — no new export mechanism is introduced.
 */
class ReportCentreController extends Controller
{
    private const FILTER_KEYS = ['from_date', 'to_date', 'hospital_id', 'status', 'plan', 'country', 'search'];

    public function __construct(private readonly ReportCentreService $reports)
    {
    }

    public function index()
    {
        return view('super-admin.reports.index', [
            'dashboard' => $this->reports->dashboardKpis(),
            'categories' => ReportCatalog::CATEGORIES,
            'catalog' => ReportCatalog::all(),
        ]);
    }

    private function requireReport(string $slug): array
    {
        $report = ReportCatalog::find($slug);
        abort_if(! $report, 404, "Unknown report [{$slug}].");

        return $report;
    }

    private function filters(Request $request): array
    {
        return array_filter(
            $request->only(self::FILTER_KEYS),
            fn ($v) => $v !== null && $v !== ''
        );
    }

    public function show(Request $request, string $slug)
    {
        $report  = $this->requireReport($slug);
        $filters = $this->filters($request);

        return view('super-admin.reports.show', [
            'slug'      => $slug,
            'report'    => $report,
            'filters'   => $filters,
            'hospitals' => Hospital::orderBy('name')->get(['id', 'name']),
            'plans'     => Plan::active()->orderBy('name')->get(['id', 'name']),
            'countries' => Hospital::whereNotNull('country')->where('country', '!=', '')->distinct()->orderBy('country')->pluck('country'),
            'columns'   => $report['type'] === 'table' ? $this->reports->tableColumns($slug) : null,
            'orderableColumns' => $report['type'] === 'table' ? $this->reports->orderableColumns($slug) : [],
            'summary'   => $report['type'] === 'summary' ? $this->reports->summary($slug, $filters) : null,
        ]);
    }

    public function data(Request $request, string $slug)
    {
        $report = $this->requireReport($slug);
        abort_if($report['type'] !== 'table', 404);

        $filters = $this->filters($request);
        $source  = $this->reports->tableQuery($slug, $filters);

        // Aggregate reports (revenue-by-plan/hospital, support-performance)
        // come back as a bounded in-memory Collection — Yajra's
        // CollectionDataTable engine handles search/sort/paginate directly
        // over the already-transformed assoc arrays, no addColumn needed.
        if ($source instanceof Collection) {
            $rows = $source->map(fn ($row) => $this->reports->rowTransform($slug, $row))->values();

            return DataTables::of($rows)->make(true);
        }

        $dt = DataTables::of($source);
        foreach ($this->reports->tableColumns($slug) as $col) {
            $key = $col['data'];
            $dt->addColumn($key, fn ($row) => $this->reports->rowTransform($slug, $row)[$key] ?? null);
        }

        // addColumn() blacklists every touched column from Yajra's default
        // ordering (QueryDataTable::addColumn() → pushToBlacklist()) — since
        // every displayed column goes through addColumn() above (so CSV/
        // table formatting stays identical via rowTransform()), explicitly
        // restore ordering for the columns ReportCentreService has vetted
        // as safe, unambiguous single-column sorts.
        foreach ($this->reports->orderableColumns($slug) as $col) {
            $dt->orderColumn($col, fn ($query, $direction) => $query->orderBy($col, $direction));
        }

        return $dt->make(true);
    }

    public function exportCsv(Request $request, string $slug): StreamedResponse
    {
        $report  = $this->requireReport($slug);
        $filters = $this->filters($request);

        // Rows are gathered up front (not just counted) so the row count
        // logged to report_exports/ActivityLog is exact, and the same
        // in-memory rows are streamed out below — no second query.
        $rows = null;
        if ($report['type'] === 'table') {
            $source = $this->reports->tableQuery($slug, $filters);
            $items  = $source instanceof Collection ? $source : $source->get();
            $rows   = $items->map(fn ($row) => $this->reports->rowTransform($slug, $row));
        }
        $summary = $report['type'] === 'summary' ? $this->reports->summary($slug, $filters) : null;

        $this->reports->logExport($slug, 'csv', $filters, $rows?->count() ?? 0);
        $columns = $report['type'] === 'table' ? $this->reports->tableColumns($slug) : null;

        return response()->streamDownload(function () use ($rows, $summary, $columns) {
            $out = fopen('php://output', 'w');

            if ($rows !== null) {
                fputcsv($out, array_map(fn ($c) => $c['title'], $columns));
                foreach ($rows as $row) {
                    unset($row['id']);
                    fputcsv($out, $row);
                }
            } else {
                // Summary reports export as "Metric,Value" — the exact
                // convention AnalyticsController::exportCsv() already uses.
                fputcsv($out, ['Metric', 'Value']);
                foreach (($summary['kpis'] ?? []) as $kpi) {
                    fputcsv($out, [$kpi['label'], $kpi['value']]);
                }
                foreach (($summary['table']['rows'] ?? []) as $row) {
                    fputcsv($out, array_values($row));
                }
            }

            fclose($out);
        }, str($report['label'])->slug() . '-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Same view rendered standalone for the browser's own print-to-PDF /
     * print preview — the exact convention ReportController's *Pdf()
     * actions and AnalyticsController::pdf() already use. One route serves
     * both "Export PDF" and "Print" since, per this codebase's established
     * pattern, they are the same mechanism.
     */
    public function print(Request $request, string $slug)
    {
        $report  = $this->requireReport($slug);
        $filters = $this->filters($request);

        $rows = null;
        if ($report['type'] === 'table') {
            $source = $this->reports->tableQuery($slug, $filters);
            $items  = $source instanceof Collection ? $source : $source->get();
            $rows   = $items->map(fn ($row) => $this->reports->rowTransform($slug, $row));
        }

        $summary = $report['type'] === 'summary' ? $this->reports->summary($slug, $filters) : null;

        $this->reports->logExport($slug, 'print', $filters, $rows?->count() ?? 0);

        return view('super-admin.reports.print', [
            'slug' => $slug,
            'report' => $report,
            'columns' => $report['type'] === 'table' ? $this->reports->tableColumns($slug) : null,
            'rows' => $rows,
            'summary' => $summary,
            'filters' => $filters,
        ]);
    }
}
