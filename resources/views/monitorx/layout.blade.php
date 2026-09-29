<!doctype html>
<html lang="en" class="h-full" x-data="{ sidebar: true, theme: localStorage.getItem('mx-theme') || 'dark' }" :class="theme === 'dark' ? 'dark' : ''" x-init="$watch('theme', v => localStorage.setItem('mx-theme', v))">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'MonitorX' }} · MonitorX</title>
    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        ink: '#080d1a',
                        surface: '#0e1525',
                        panel: '#131c30',
                        border: '#1e2d47',
                        muted: '#6b7fa3',
                        accent: '#6366f1',
                        'accent-light': '#818cf8',
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'fade-in': 'fadeIn 0.2s ease-out',
                        'slide-in': 'slideIn 0.25s ease-out',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0', transform: 'translateY(-4px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        slideIn: { '0%': { opacity: '0', transform: 'translateX(-8px)' }, '100%': { opacity: '1', transform: 'translateX(0)' } },
                    },
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { background-color: #080d1a; }
        .dark body { background-color: #080d1a; }
        pre, code { white-space: pre-wrap; word-break: break-word; }
        ::selection { background: #4f46e5; color: white; }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #1e2d47; border-radius: 2px; }
        ::-webkit-scrollbar-thumb:hover { background: #2a3f60; }
        .nav-item { transition: all 0.15s ease; }
        .nav-item:hover { background: rgba(99, 102, 241, 0.08); }
        .nav-item.active { background: rgba(99, 102, 241, 0.15); }
        .status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 500; }
        .card { background: #131c30; border: 1px solid #1e2d47; border-radius: 12px; }
        .btn-primary { background: #6366f1; color: white; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 500; transition: all 0.15s; }
        .btn-primary:hover { background: #4f46e5; transform: translateY(-1px); }
        .btn-secondary { background: transparent; border: 1px solid #1e2d47; color: #94a3b8; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 500; transition: all 0.15s; }
        .btn-secondary:hover { border-color: #6366f1; color: #818cf8; }
        .input-field { background: #0e1525; border: 1px solid #1e2d47; color: #e2e8f0; border-radius: 8px; padding: 6px 12px; font-size: 12px; transition: border-color 0.15s; outline: none; }
        .input-field:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1); }
        .input-field::placeholder { color: #4a5568; }
        .table-row { transition: background 0.1s; }
        .table-row:hover { background: rgba(255, 255, 255, 0.02); }
        .sidebar-transition { transition: width 0.2s ease, transform 0.2s ease; }
        .glow { box-shadow: 0 0 20px rgba(99, 102, 241, 0.15); }
        .metric-card { position: relative; overflow: hidden; }
        .metric-card::before { content: ''; position: absolute; top: 0; right: 0; width: 80px; height: 80px; border-radius: 50%; opacity: 0.05; transform: translate(20px, -20px); }
        @keyframes spin-slow { to { transform: rotate(360deg); } }
        .live-indicator { animation: pulse 2s ease-in-out infinite; }
    </style>
    @stack('head')
</head>
<body class="h-full font-sans text-slate-100 antialiased" style="background-color: #080d1a">

<div class="flex h-screen overflow-hidden" x-data="{ mobileOpen: false }">

    {{-- Mobile overlay --}}
    <div x-show="mobileOpen" @click="mobileOpen = false"
         class="fixed inset-0 z-20 bg-black/50 backdrop-blur-sm lg:hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-30 flex flex-col border-r border-border sidebar-transition lg:relative lg:translate-x-0"
           :class="mobileOpen ? 'translate-x-0 w-64' : '-translate-x-full w-64 lg:translate-x-0'"
           style="background-color: #0a1020;"
           :style="sidebar ? 'width: 256px' : 'width: 68px'">

        {{-- Logo --}}
        <div class="flex h-[60px] shrink-0 items-center justify-between border-b border-border px-3.5">
            <a href="{{ route('monitorx.dashboard') }}" class="flex items-center gap-2.5 overflow-hidden group">
                <img src="{{ asset('images/logo.png') }}" alt="MonitorX" class="h-8 w-auto object-contain max-w-[170px] transition-transform duration-200 group-hover:scale-[1.02]" x-show="sidebar" x-transition.opacity>
                <img src="{{ asset('images/logo-icon.png') }}" alt="MonitorX" class="h-8 w-8 object-contain shrink-0 transition-transform duration-200 group-hover:scale-105" x-show="!sidebar" x-transition.opacity style="display: none;">
            </a>
            <button @click="sidebar = !sidebar" class="ml-auto hidden shrink-0 rounded-md p-1.5 text-muted hover:bg-white/5 lg:flex" :title="sidebar ? 'Collapse' : 'Expand'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex flex-1 flex-col overflow-y-auto px-3 py-4 space-y-0.5">
            @php
            $navSections = [
                'Workspace' => [
                    ['route' => 'monitorx.dashboard', 'label' => 'Dashboard', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
                    ['route' => 'monitorx.search', 'label' => 'Global Search', 'icon' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>'],
                ],
                'Monitoring' => [
                    ['route' => 'monitorx.index', 'module' => 'requests', 'label' => 'Requests', 'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
                    ['route' => 'monitorx.index', 'module' => 'commands', 'label' => 'Commands', 'icon' => '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>'],
                    ['route' => 'monitorx.index', 'module' => 'schedule', 'label' => 'Schedule', 'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                    ['route' => 'monitorx.index', 'module' => 'jobs', 'label' => 'Jobs', 'icon' => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>'],
                    ['route' => 'monitorx.index', 'module' => 'batches', 'label' => 'Batches', 'icon' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>'],
                    ['route' => 'monitorx.index', 'module' => 'cache', 'label' => 'Cache', 'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>'],
                    ['route' => 'monitorx.index', 'module' => 'dumps', 'label' => 'Dumps', 'icon' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>'],
                    ['route' => 'monitorx.index', 'module' => 'events', 'label' => 'Events', 'icon' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>'],
                    ['route' => 'monitorx.index', 'module' => 'exceptions', 'label' => 'Exceptions', 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>', 'alert' => true],
                    ['route' => 'monitorx.index', 'module' => 'gates', 'label' => 'Gates', 'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
                    ['route' => 'monitorx.index', 'module' => 'http-client', 'label' => 'HTTP Client', 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'],
                    ['route' => 'monitorx.index', 'module' => 'logs', 'label' => 'Logs', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>'],
                    ['route' => 'monitorx.index', 'module' => 'mail', 'label' => 'Mail', 'icon' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>'],
                    ['route' => 'monitorx.index', 'module' => 'models', 'label' => 'Models', 'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>'],
                    ['route' => 'monitorx.index', 'module' => 'notifications', 'label' => 'Notifications', 'icon' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>'],
                    ['route' => 'monitorx.index', 'module' => 'queries', 'label' => 'Queries', 'icon' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
                    ['route' => 'monitorx.index', 'module' => 'redis', 'label' => 'Redis', 'icon' => '<rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>'],
                    ['route' => 'monitorx.index', 'module' => 'views', 'label' => 'Views', 'icon' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'],
                ],
                'Tools' => [
                    ['route' => 'monitorx.saved-filters.index', 'label' => 'Saved Filters', 'icon' => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>'],
                    ['route' => 'monitorx.settings', 'label' => 'Settings', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M21 12h-2M5 12H3m15.07 7.07l-1.41-1.41M4.93 19.07l1.41-1.41M12 19v2M12 5V3"/>'],
                ],
            ];
            @endphp

            @foreach($navSections as $section => $items)
                <div class="mb-1">
                    <div x-show="sidebar" x-transition.opacity class="px-3 py-2 text-[9px] font-semibold uppercase tracking-widest text-slate-600">{{ $section }}</div>
                    @foreach($items as $item)
                        @php
                            $isActive = isset($item['module'])
                                ? request()->route('module') === $item['module']
                                : request()->routeIs($item['route']);
                            $href = isset($item['module'])
                                ? route($item['route'], $item['module'])
                                : route($item['route']);
                        @endphp
                        <a href="{{ $href }}"
                           class="nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-xs {{ $isActive ? 'active text-indigo-300' : 'text-slate-400 hover:text-slate-200' }}"
                           :title="!sidebar ? '{{ $item['label'] }}' : ''">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 {{ $isActive ? 'text-indigo-400' : '' }}">{!! $item['icon'] !!}</svg>
                            <span x-show="sidebar" x-transition.opacity class="truncate font-medium">{{ $item['label'] }}</span>
                            @if(isset($item['alert']) && $item['alert'])
                                <span x-show="sidebar" class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-rose-400 animate-pulse-slow"></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        {{-- Footer --}}
        <div class="shrink-0 border-t border-border px-3 py-3" x-show="sidebar" x-transition.opacity>
            <div class="rounded-lg bg-white/[0.02] px-3 py-2">
                <div class="flex items-center justify-between text-[10px]">
                    <span class="font-medium text-slate-400">Retention</span>
                    <span class="text-emerald-400">{{ config('monitorx.retention_days') }}d</span>
                </div>
                <div class="mt-1 text-[10px] text-slate-600">{{ app()->environment() }} environment</div>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">

        {{-- Top bar --}}
        <header class="flex h-[60px] shrink-0 items-center justify-between border-b border-border px-4 md:px-6" style="background-color: #080d1a;">

            {{-- Mobile brand & menu --}}
            <div class="flex items-center gap-2.5 lg:hidden">
                <button @click="mobileOpen = true" class="p-1 text-muted hover:text-white" aria-label="Open navigation menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <a href="{{ route('monitorx.dashboard') }}" class="flex items-center">
                    <img src="{{ asset('images/logo.png') }}" alt="MonitorX" class="h-6 w-auto object-contain max-w-[130px]">
                </a>
            </div>

            {{-- Breadcrumb --}}
            <div class="hidden items-center gap-2 text-xs text-muted sm:flex">
                <a href="{{ route('monitorx.dashboard') }}" class="hover:text-white">MonitorX</a>
                <span>/</span>
                <span class="text-slate-300">{{ $crumb ?? 'Dashboard' }}</span>
            </div>

            {{-- Right side --}}
            <div class="flex items-center gap-2 ml-auto">
                {{-- Search --}}
                <form action="{{ route('monitorx.search') }}" class="hidden sm:block">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-muted" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input name="q" value="{{ request('q') }}" placeholder="Search all modules…"
                               class="w-52 rounded-lg border border-border bg-surface pl-8 pr-3 py-1.5 text-xs text-white outline-none placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 xl:w-72">
                    </div>
                </form>

                {{-- Environment badge --}}
                <span class="hidden rounded-full border border-border px-2.5 py-1 text-[10px] font-medium text-emerald-400 sm:inline-flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 live-indicator"></span>
                    {{ app()->environment() }}
                </span>

                {{-- Theme toggle --}}
                <button @click="theme = theme === 'dark' ? 'light' : 'dark'" class="rounded-md p-1.5 text-muted hover:bg-white/5 hover:text-white">
                    <svg x-show="theme === 'dark'" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    <svg x-show="theme !== 'dark'" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>

                {{-- User avatar --}}
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600/20 text-[10px] font-semibold text-indigo-300 ring-1 ring-indigo-500/30">
                    MX
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="mx-4 mt-3 flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-xs text-emerald-300 animate-fade-in"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
