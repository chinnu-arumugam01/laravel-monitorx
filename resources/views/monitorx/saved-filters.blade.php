@extends('monitorx.layout', ['title' => 'Saved Filters', 'crumb' => 'Saved Filters'])

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold">Saved Filters</h2>
        <p class="mt-0.5 text-xs text-muted">Reusable filter presets for quick monitoring access.</p>
    </div>
    <button onclick="document.getElementById('new-filter-modal').showModal()" class="btn-primary flex items-center gap-2">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Preset
    </button>
</div>

@if($filters->isEmpty())
<div class="card p-12 text-center">
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500/10 ring-1 ring-indigo-500/20">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="text-indigo-400">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
        </svg>
    </div>
    <div class="mt-4 text-sm font-medium text-slate-300">No saved filter presets yet</div>
    <p class="mt-1 text-xs text-muted">Save filters from any module's filter panel to quickly reapply them later.</p>
</div>
@else
<div class="space-y-5">
    @foreach($filters as $module => $moduleFilters)
    <section>
        <h3 class="mb-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
            {{ $module ? config('monitorx.modules.'.$module, $module) : 'Global (All Modules)' }}
        </h3>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($moduleFilters as $filter)
            <div class="card flex items-start justify-between gap-3 p-4 hover:border-border/80 transition-colors">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-slate-200">{{ $filter->name }}</span>
                        <span class="status-badge bg-indigo-500/10 text-indigo-400">{{ $filter->logic }}</span>
                    </div>
                    <div class="mt-2 space-y-1">
                        @foreach(array_slice($filter->conditions, 0, 3) as $condition)
                        <div class="text-[10px] text-muted">
                            <span class="font-medium text-slate-500">{{ $condition['field'] ?? '' }}</span>
                            <span class="mx-1 text-slate-600">{{ $condition['operator'] ?? '=' }}</span>
                            <span class="font-mono text-[9px]">{{ is_array($condition['value'] ?? null) ? implode(', ', $condition['value']) : ($condition['value'] ?? '') }}</span>
                        </div>
                        @endforeach
                        @if(count($filter->conditions) > 3)
                            <div class="text-[9px] text-slate-600">+ {{ count($filter->conditions) - 3 }} more conditions</div>
                        @endif
                    </div>
                    <div class="mt-2 text-[9px] text-slate-600">Created {{ $filter->created_at->diffForHumans() }}</div>
                </div>
                <div class="flex shrink-0 flex-col gap-2">
                    @if($module)
                    <a href="{{ route('monitorx.index', ['module' => $module, 'conditions' => json_encode($filter->conditions), 'logic' => $filter->logic]) }}"
                       class="text-[10px] text-indigo-400 hover:text-indigo-300">Apply →</a>
                    @endif
                    <form action="{{ route('monitorx.saved-filters.destroy', $filter) }}" method="POST" onsubmit="return confirm('Delete this filter preset?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-[10px] text-rose-500 hover:text-rose-400">Delete</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endforeach
</div>
@endif

{{-- Create filter modal --}}
<dialog id="new-filter-modal" class="rounded-xl border border-border bg-panel p-5 shadow-2xl backdrop:bg-black/60 w-full max-w-md">
    <h3 class="text-sm font-semibold">Create Filter Preset</h3>
    <form action="{{ route('monitorx.saved-filters.store') }}" method="POST" class="mt-4 space-y-4">
        @csrf
        <div>
            <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">Name</label>
            <input name="name" required placeholder="My filter…" class="input-field w-full">
        </div>
        <div>
            <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">Module (optional)</label>
            <select name="module" class="input-field w-full">
                <option value="">All modules</option>
                @foreach(config('monitorx.modules') as $slug => $label)
                    <option value="{{ $slug }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-[10px] uppercase tracking-wider text-muted">Logic</label>
            <select name="logic" class="input-field w-full">
                <option value="AND">AND — All conditions must match</option>
                <option value="OR">OR — Any condition can match</option>
            </select>
        </div>
        <input type="hidden" name="conditions" value="[]">
        <div class="flex gap-2 pt-2">
            <button type="submit" class="btn-primary">Create</button>
            <button type="button" onclick="document.getElementById('new-filter-modal').close()" class="btn-secondary">Cancel</button>
        </div>
    </form>
</dialog>
@endsection
