<?php

namespace App\Monitoring;

use App\Models\MonitoringRecord;
use Illuminate\Support\Facades\Context;
use Throwable;

class Recorder
{
    public function record(string $module, string $event, array $data = []): ?MonitoringRecord
    {
        if (! config('monitorx.enabled') || ! array_key_exists($module, config('monitorx.modules', []))) {
            return null;
        }

        try {
            $redactor = app(Redactor::class);
            $payload = $redactor->redact($data);

            return MonitoringRecord::create([
                'module' => $module,
                'event' => $event,
                'status' => $payload['status'] ?? null,
                'environment' => app()->environment(),
                'application' => config('app.name'),
                'correlation_id' => $payload['correlation_id'] ?? Context::get('monitorx_correlation_id'),
                'user_id' => $payload['user_id'] ?? null,
                'duration_ms' => isset($payload['duration_ms']) ? (int) $payload['duration_ms'] : null,
                'summary' => isset($payload['summary']) ? mb_substr((string) $payload['summary'], 0, 500) : null,
                'payload' => $payload,
                'occurred_at' => $payload['occurred_at'] ?? now(),
            ]);
        } catch (Throwable) {
            // Monitoring must never interrupt the application being monitored.
            return null;
        }
    }
}
