@extends('monitorx.layout', ['title' => 'Global Search', 'crumb' => 'Global Search'])

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold">Global Search</h2>
    <p class="mt-0.5 text-xs text-muted">Search across all 18 monitoring modules simultaneously.</p>
</div>

<form class="mb-5" method="GET" action="{{ route('monitorx.search') }}">
    <div class="flex gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 text-muted" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input name="q" value="{{ $term }}" placeholder="Search URLs, exceptions, SQL, jobs, events, logs, models, cache keys…"
                   autofocus
                   class="input-field w-full py-3 pl-12 pr-4 text-sm">
        </div>
        <select name="module" class="input-field">
            <option value="">All modules</option>
            @foreach($modules as $slug => $label)
                <option value="{{ $slug }}" @selected($module === $slug)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-primary px-6 py-3">Search</button>
    </div>
</form>

@if($term)
<div class="mb-4 flex items-center gap-3">
    <span class="text-xs text-muted">
        Found <span class="font-semibold text-white">{{ number_format($records->total()) }}</span> results for
        "<span class="text-indigo-300">{{ $term }}</span>"
        @if($module) in <span class="text-indigo-300">{{ $modules[$module] ?? $module }}</span> @endif
    </span>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="border-b border-border bg-white/[.015] text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3">Module</th>
                    <th class="px-4 py-3">Summary</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Environment</th>
                    <th class="px-4 py-3">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($records as $record)
                <tr class="table-row">
                    <td class="px-5 py-3.5">
                        <span class="status-badge bg-indigo-500/10 text-indigo-400">
                            {{ config('monitorx.modules.'.$record->module, $record->module) }}
                        </span>
                    </td>
                    <td class="max-w-sm px-4 py-3.5">
                        <a href="{{ route('monitorx.show', [$record->module, $record]) }}"
                           class="block truncate font-medium text-slate-200 hover:text-indigo-300 transition-colors">
                            {{ $record->summary ?: $record->event }}
                        </a>
                        @if($record->correlation_id)
                        <div class="mt-0.5 font-mono text-[9px] text-slate-600">{{ $record->correlation_id }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3.5">
                        @if($record->status)
                        <span class="status-badge {{ match($record->status) {
                            'failed','denied' => 'bg-rose-500/10 text-rose-400',
                            'slow' => 'bg-amber-500/10 text-amber-400',
                            'success','allowed','submitted','hit' => 'bg-emerald-500/10 text-emerald-400',
                            default => 'bg-slate-500/10 text-slate-400',
                        } }}">{{ $record->status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-[10px] text-muted">{{ $record->environment }}</td>
                    <td class="whitespace-nowrap px-4 py-3.5 text-[10px] text-muted">{{ $record->occurred_at->format('M j, H:i:s') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-16 text-center">
                        <div class="text-sm text-slate-400">No results found</div>
                        <p class="mt-1 text-xs text-muted">Try different search terms</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
    <div class="border-t border-border px-5 py-4">{{ $records->links('monitorx.pagination') }}</div>
    @endif
</div>
@else
<div class="card p-10 text-center">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/10 ring-1 ring-indigo-500/20">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="text-indigo-400">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
    </div>
    <div class="mt-4 text-sm font-medium text-slate-300">Search across all modules</div>
    <p class="mt-2 text-xs text-muted max-w-sm mx-auto">Enter a search term to find records across requests, exceptions, queries, jobs, events, logs, and more.</p>
    <div class="mt-6 flex flex-wrap justify-center gap-2">
        @foreach(config('monitorx.modules') as $slug => $label)
        <a href="{{ route('monitorx.index', $slug) }}" class="status-badge bg-white/5 text-slate-400 hover:bg-indigo-500/10 hover:text-indigo-400 transition-colors">{{ $label }}</a>
        @endforeach
    </div>
</div>
@endif
@endsection
