@extends('monitorx.layout', ['title' => config('monitorx.modules.'.$module), 'crumb' => config('monitorx.modules.'.$module)])

@section('content')
<div x-data="{
    showFilters: {{ request()->hasAny(['status','environment','from','to','min_duration','max_duration','correlation_id']) ? 'true' : 'false' }},
    showSaveModal: false,
    filterName: '',
    conditions: [],
    addCondition() {
        this.conditions.push({ field: 'status', operator: '=', value: '' });
    },
    removeCondition(i) {
        this.conditions.splice(i, 1);
    },
}">

{{-- Page header --}}
<div class="mb-5 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h2 class="text-xl font-semibold">{{ config('monitorx.modules.'.$module) }}</h2>
        <p class="mt-0.5 text-xs text-muted">{{ $records->total() }} records collected &mdash; inspect, filter and export.</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="showFilters = !showFilters"
                class="flex items-center gap-2 btn-secondary"
                :class="showFilters ? 'border-indigo-500/50 text-indigo-300' : ''">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filters
            @if(request()->hasAny(['status','environment','from','to','min_duration','max_duration','correlation_id','q']))
                <span class="flex h-4 w-4 items-center justify-center rounded-full bg-indigo-500 text-[9px] font-bold text-white">!</span>
            @endif
        </button>
        <a href="{{ route('monitorx.export', array_merge(['module' => $module], request()->query())) }}"
           class="flex items-center gap-2 btn-secondary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </a>
    </div>
</div>

{{-- Filter Panel --}}
<div x-show="showFilters"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-2"
     class="mb-5">
    <form method="GET" action="{{ route('monitorx.index', $module) }}" class="card p-4" id="filter-form">
        <div class="mb-3 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-300">Filter Records</span>
            <div class="flex items-center gap-2">
                {{-- Saved Filters Dropdown --}}
                @if($savedFilters->count())
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex items-center gap-1.5 text-[10px] text-indigo-400 hover:text-indigo-300">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        Saved presets
                    </button>
                    <div x-show="open" @click.away="open = false"
                         class="absolute right-0 top-6 z-10 w-52 rounded-lg border border-border bg-panel shadow-xl"
                         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                        @foreach($savedFilters as $sf)
                        <a href="{{ route('monitorx.index', array_merge(['module' => $module], ['conditions' => json_encode($sf->conditions), 'logic' => $sf->logic])) }}"
                           class="flex items-center gap-2 px-3 py-2 text-[11px] text-slate-300 hover:bg-white/5 first:rounded-t-lg last:rounded-b-lg">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            {{ $sf->name }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(request()->hasAny(['q','status','environment','from','to','min_duration','max_duration','correlation_id']))
                    <a href="{{ route('monitorx.index', $module) }}" class="text-[10px] text-rose-400 hover:text-rose-300">Clear all</a>
                @endif
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {{-- Search --}}
            <div class="sm:col-span-2">
                <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">Search</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-muted" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input name="q" value="{{ request('q') }}" placeholder="Search summaries, payloads, correlation IDs…"
                           class="input-field w-full pl-9">
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">Status</label>
                <select name="status" class="input-field w-full">
                    <option value="">Any status</option>
                    @foreach($statuses as $s)
                        <option @selected(request('status') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Environment --}}
            <div>
                <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">Environment</label>
                <select name="environment" class="input-field w-full">
                    <option value="">Any environment</option>
                    @foreach($environments as $env)
                        <option @selected(request('environment') === $env)>{{ $env }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date range --}}
            <div>
                <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="input-field w-full">
            </div>
            <div>
                <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="input-field w-full">
            </div>

            {{-- Duration --}}
            <div>
                <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">Min Duration (ms)</label>
                <input type="number" name="min_duration" value="{{ request('min_duration') }}" placeholder="0" class="input-field w-full">
            </div>
            <div>
                <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">Max Duration (ms)</label>
                <input type="number" name="max_duration" value="{{ request('max_duration') }}" placeholder="∞" class="input-field w-full">
            </div>

            {{-- Correlation ID --}}
            <div>
                <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">Correlation ID</label>
                <input name="correlation_id" value="{{ request('correlation_id') }}" placeholder="UUID…" class="input-field w-full font-mono">
            </div>

            {{-- Page size --}}
            <div>
                <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">Per page</label>
                <select name="per_page" class="input-field w-full">
                    @foreach([10, 25, 50, 100] as $size)
                        <option @selected((int)request('per_page', 25) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2 border-t border-border pt-4">
            <button type="submit" class="btn-primary">Apply Filters</button>
            <a href="{{ route('monitorx.index', $module) }}" class="btn-secondary">Reset</a>
            <button type="button" @click="showSaveModal = true" class="ml-auto text-[10px] text-indigo-400 hover:text-indigo-300">
                + Save as preset
            </button>
        </div>
    </form>

    {{-- Save Filter Modal --}}
    <div x-show="showSaveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition>
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showSaveModal = false"></div>
        <div class="relative z-10 w-full max-w-sm rounded-xl border border-border bg-panel p-5 shadow-2xl">
            <h3 class="text-sm font-semibold">Save Filter Preset</h3>
            <form action="{{ route('monitorx.saved-filters.store') }}" method="POST" class="mt-4">
                @csrf
                <input type="hidden" name="module" value="{{ $module }}">
                <input type="hidden" name="conditions" value="{{ json_encode([]) }}">
                <input type="hidden" name="logic" value="AND">
                <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">Preset name</label>
                <input name="name" x-model="filterName" required placeholder="My filter preset…" class="input-field w-full">
                <div class="mt-4 flex gap-2">
                    <button type="submit" class="btn-primary">Save</button>
                    <button type="button" @click="showSaveModal = false" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Results table --}}
<div class="card overflow-hidden">
    {{-- Table header --}}
    <div class="flex items-center justify-between border-b border-border px-5 py-3">
        <span class="text-xs text-muted">
            Showing {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} of {{ number_format($records->total()) }} records
        </span>
        <div class="flex items-center gap-3 text-[10px] text-muted">
            <span>Newest first</span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="border-b border-border bg-white/[.015] text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3">Summary</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Duration</th>
                    <th class="px-4 py-3">Environment</th>
                    <th class="px-4 py-3">Correlation ID</th>
                    <th class="px-4 py-3">Time</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($records as $record)
                <tr class="table-row">
                    <td class="max-w-xs px-5 py-3.5">
                        <a href="{{ route('monitorx.show', [$module, $record]) }}"
                           class="block truncate font-medium text-slate-200 hover:text-indigo-300 transition-colors">
                            {{ $record->summary ?: $record->event }}
                        </a>
                        <div class="mt-0.5 text-[10px] text-slate-600">{{ $record->event }}</div>
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="status-badge {{ match($record->status) {
                            'failed','denied' => 'bg-rose-500/10 text-rose-400',
                            'slow' => 'bg-amber-500/10 text-amber-400',
                            'pending','running' => 'bg-sky-500/10 text-sky-400',
                            'success','allowed','submitted','hit' => 'bg-emerald-500/10 text-emerald-400',
                            'miss' => 'bg-orange-500/10 text-orange-400',
                            default => 'bg-slate-500/10 text-slate-400',
                        } }}">{{ $record->status ?: 'recorded' }}</span>
                    </td>
                    <td class="px-4 py-3.5 font-mono text-[10px] {{ $record->isSlow() ? 'text-amber-400' : 'text-slate-400' }}">
                        {{ $record->duration_ms !== null ? number_format($record->duration_ms).' ms' : '—' }}
                    </td>
                    <td class="px-4 py-3.5 text-[10px] text-muted">{{ $record->environment }}</td>
                    <td class="px-4 py-3.5 font-mono text-[10px] text-muted">
                        @if($record->correlation_id)
                            <a href="{{ route('monitorx.index', ['module' => $module, 'correlation_id' => $record->correlation_id]) }}"
                               class="hover:text-indigo-400 transition-colors" title="{{ $record->correlation_id }}">
                                {{ Str::limit($record->correlation_id, 14) }}
                            </a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3.5 text-[10px] text-muted">{{ $record->occurred_at->format('M j, H:i:s') }}</td>
                    <td class="px-4 py-3.5">
                        <a href="{{ route('monitorx.show', [$module, $record]) }}"
                           class="text-[10px] text-slate-500 hover:text-indigo-400 transition-colors">View →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-16 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white/5 mb-3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="text-muted">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </div>
                        <div class="text-sm text-slate-400">No records match these filters</div>
                        <p class="mt-1 text-xs text-muted">Try adjusting your search or filter criteria</p>
                        <a href="{{ route('monitorx.index', $module) }}" class="mt-4 inline-block text-xs text-indigo-400 hover:text-indigo-300">Clear filters</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($records->hasPages())
    <div class="border-t border-border px-5 py-4">
        {{ $records->onEachSide(2)->links('monitorx.pagination') }}
    </div>
    @endif
</div>

</div>
@endsection
