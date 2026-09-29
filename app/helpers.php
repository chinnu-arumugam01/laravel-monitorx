<?php

use App\Monitoring\Recorder;

if (! function_exists('monitorx_dump')) {
    /**
     * Capture a value to MonitorX Dumps module for developer inspection.
     * Only operates in non-production environments by default.
     */
    function monitorx_dump(mixed $value, string $label = ''): mixed
    {
        if (! config('monitorx.dumps_enabled', true)) {
            return $value;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[0] ?? [];

        app(Recorder::class)->record('dumps', 'dump', [
            'summary' => $label ?: ('Dump at '.basename($caller['file'] ?? 'unknown').':'.($caller['line'] ?? 0)),
            'label' => $label,
            'type' => get_debug_type($value),
            'value' => is_scalar($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'file' => $caller['file'] ?? null,
            'line' => $caller['line'] ?? null,
            'status' => 'recorded',
        ]);

        return $value;
    }
}
