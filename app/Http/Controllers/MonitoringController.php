<?php

namespace App\Http\Controllers;

use App\Models\MonitoringRecord;
use App\Models\SavedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonitoringController extends Controller
{
    /** @return array<string, mixed> */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'q' => $request->string('q')->toString(),
            'status' => $request->string('status')->toString(),
            'environment' => $request->string('environment')->toString(),
            'correlation_id' => $request->string('correlation_id')->toString(),
            'user_id' => $request->input('user_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'min_duration' => $request->input('min_duration'),
            'max_duration' => $request->input('max_duration'),
        ];
    }

    private function baseQuery(string $module, Request $request): Builder
    {
        abort_unless(array_key_exists($module, config('monitorx.modules')), 404);

        $filters = $this->filtersFromRequest($request);

        return MonitoringRecord::applyFilters(
            MonitoringRecord::query()->module($module)->orderByDesc('occurred_at'),
            $filters
        );
    }

    public function dashboard(Request $request): View
    {
        $base = MonitoringRecord::query()
            ->when($request->filled('environment'), fn ($q) => $q->where('environment', $request->string('environment')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('occurred_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('occurred_at', '<=', $request->input('to')));

        $counts = (clone $base)
            ->selectRaw('module, count(*) as total')
            ->groupBy('module')
            ->pluck('total', 'module');

        $cacheHits = (clone $base)->where('module', 'cache')->where('status', 'hit')->count();
        $cacheMisses = (clone $base)->where('module', 'cache')->where('status', 'miss')->count();
        $cacheTotal = $cacheHits + $cacheMisses;

        $driver = DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'mysql', 'mariadb' => "DATE_FORMAT(occurred_at, '%Y-%m-%d')",
            'pgsql' => "TO_CHAR(occurred_at, 'YYYY-MM-DD')",
            'sqlsrv' => "FORMAT(occurred_at, 'yyyy-MM-dd')",
            default => "strftime('%Y-%m-%d', occurred_at)",
        };

        // Request volume over last 14 days for chart
        $requestVolume = (clone $base)
            ->where('module', 'requests')
            ->selectRaw("{$dateExpr} as day, count(*) as total")
            ->where('occurred_at', '>=', now()->subDays(14))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        // Exception trend
        $exceptionTrend = (clone $base)
            ->where('module', 'exceptions')
            ->selectRaw("{$dateExpr} as day, count(*) as total")
            ->where('occurred_at', '>=', now()->subDays(14))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        // Fill missing dates
        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));
        $requestVolumeChart = $days->mapWithKeys(fn ($day) => [$day => $requestVolume[$day] ?? 0]);
        $exceptionTrendChart = $days->mapWithKeys(fn ($day) => [$day => $exceptionTrend[$day] ?? 0]);

        // Status distribution for requests
        $statusDistribution = (clone $base)
            ->where('module', 'requests')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'requests' => $counts['requests'] ?? 0,
            'exceptions' => $counts['exceptions'] ?? 0,
            'queries' => $counts['queries'] ?? 0,
            'slow_queries' => (clone $base)->where('module', 'queries')->where('status', 'slow')->count(),
            'failed_jobs' => (clone $base)->where('module', 'jobs')->where('status', 'failed')->count(),
            'failed_batches' => (clone $base)->where('module', 'batches')->where('status', 'failed')->count(),
            'cache_hit_ratio' => $cacheTotal > 0 ? round(($cacheHits / $cacheTotal) * 100) : 0,
            'http_failures' => (clone $base)->where('module', 'http-client')->where('status', 'failed')->count(),
            'mail_failures' => (clone $base)->where('module', 'mail')->where('status', 'failed')->count(),
            'notification_failures' => (clone $base)->where('module', 'notifications')->where('status', 'failed')->count(),
            'avg_duration' => (int) ((clone $base)->where('module', 'requests')->avg('duration_ms') ?? 0),
        ];

        $recent = (clone $base)->latest('occurred_at')->limit(12)->get();
        $environments = MonitoringRecord::select('environment')->distinct()->pluck('environment');

        return view('monitorx.dashboard', compact(
            'stats', 'recent', 'counts', 'environments',
            'requestVolumeChart', 'exceptionTrendChart', 'statusDistribution'
        ));
    }

    public function index(Request $request, string $module): View
    {
        $records = $this->baseQuery($module, $request)
            ->paginate(min(max((int) $request->input('per_page', 25), 10), 100))
            ->withQueryString();

        $savedFilters = SavedFilter::where('module', $module)->orWhereNull('module')->orderBy('name')->get();
        $environments = MonitoringRecord::select('environment')->distinct()->pluck('environment');
        $statuses = MonitoringRecord::where('module', $module)->select('status')->distinct()->whereNotNull('status')->pluck('status');

        return view('monitorx.index', compact('module', 'records', 'savedFilters', 'environments', 'statuses'));
    }

    public function show(string $module, MonitoringRecord $record): View
    {
        abort_unless($record->module === $module, 404);

        $timeline = $record->correlation_id
            ? MonitoringRecord::where('correlation_id', $record->correlation_id)->orderBy('occurred_at')->get()
            : collect();

        $related = MonitoringRecord::where('correlation_id', $record->correlation_id)
            ->where('id', '!=', $record->id)
            ->latest('occurred_at')
            ->limit(20)
            ->get()
            ->groupBy('module');

        return view('monitorx.show', compact('module', 'record', 'timeline', 'related'));
    }

    public function search(Request $request): View
    {
        $term = $request->string('q')->toString();
        $module = $request->string('module')->toString();

        $query = MonitoringRecord::query()
            ->when($term, fn ($q) => $q->search($term))
            ->when($module, fn ($q) => $q->where('module', $module))
            ->latest('occurred_at');

        $records = $query->paginate(25)->withQueryString();
        $modules = config('monitorx.modules', []);

        return view('monitorx.search', compact('records', 'term', 'module', 'modules'));
    }

    public function export(Request $request, string $module): StreamedResponse
    {
        $records = $this->baseQuery($module, $request);

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'time', 'event', 'status', 'duration_ms', 'environment', 'correlation_id', 'user_id', 'summary', 'payload']);
            $records->chunk(500, fn ($chunk) => $chunk->each(
                fn ($row) => fputcsv($out, [
                    $row->id,
                    $row->occurred_at,
                    $row->event,
                    $row->status,
                    $row->duration_ms,
                    $row->environment,
                    $row->correlation_id,
                    $row->user_id,
                    $row->summary,
                    json_encode($row->payload),
                ])
            ));
            fclose($out);
        }, Str::slug($module).'-monitoring.csv', ['Content-Type' => 'text/csv']);
    }

    public function savedFiltersIndex(): View
    {
        $filters = SavedFilter::orderBy('name')->get()->groupBy('module');

        return view('monitorx.saved-filters', compact('filters'));
    }

    public function savedFiltersStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'module' => ['nullable', 'string', 'max:32'],
            'conditions' => ['required', 'string'],
            'logic' => ['required', 'in:AND,OR'],
        ]);

        SavedFilter::create([
            'name' => $validated['name'],
            'module' => $validated['module'] ?: null,
            'conditions' => json_decode($validated['conditions'], true) ?? [],
            'logic' => $validated['logic'],
        ]);

        return back()->with('success', 'Filter preset saved.');
    }

    public function savedFiltersDestroy(SavedFilter $filter): RedirectResponse
    {
        $filter->delete();

        return back()->with('success', 'Filter preset deleted.');
    }

    public function settings(): View
    {
        $driver = DB::connection()->getDriverName();
        $payloadLengthExpr = match ($driver) {
            'pgsql' => 'sum(length(payload::text))',
            default => 'sum(length(payload))',
        };

        $totalRecords = MonitoringRecord::count();
        $diskUsage = MonitoringRecord::selectRaw("count(*) as cnt, {$payloadLengthExpr} as payload_size")->first();
        $moduleCounts = MonitoringRecord::selectRaw('module, count(*) as total')->groupBy('module')->pluck('total', 'module');
        $environments = MonitoringRecord::select('environment')->distinct()->pluck('environment');
        $config = config('monitorx');

        return view('monitorx.settings', compact('totalRecords', 'diskUsage', 'moduleCounts', 'environments', 'config'));
    }
}
