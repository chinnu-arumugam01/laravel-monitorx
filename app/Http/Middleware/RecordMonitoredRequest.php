<?php

namespace App\Http\Middleware;

use App\Monitoring\Recorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecordMonitoredRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);
        $id = Str::isUuid($request->header('X-Correlation-ID')) ? $request->header('X-Correlation-ID') : (string) Str::uuid();
        Context::add('monitorx_correlation_id', $id);
        $request->attributes->set('monitorx_correlation_id', $id);

        try {
            $response = $next($request);
            $response->headers->set('X-Correlation-ID', $id);
            $path = trim($request->path(), '/');
            $monitorPath = trim((string) config('monitorx.path', 'monitorx'), '/');
            if ($path !== '' && $path !== $monitorPath && ! $request->is($monitorPath.'/*')) {
                app(Recorder::class)->record('requests', 'request', [
                    'summary' => $request->method().' '.$request->fullUrl(), 'url' => $request->fullUrl(), 'route' => $request->route()?->getName(),
                    'method' => $request->method(), 'status_code' => $response->getStatusCode(), 'status' => $response->getStatusCode() >= 400 ? 'failed' : 'success',
                    'duration_ms' => (int) ((microtime(true) - $started) * 1000), 'memory_bytes' => memory_get_peak_usage(true),
                    'ip' => $request->ip(), 'user_id' => $request->user()?->getAuthIdentifier(), 'headers' => $request->headers->all(),
                    'request' => $request->except(config('monitorx.redact')), 'response_headers' => $response->headers->all(),
                    'correlation_id' => $id, 'middleware' => $request->route()?->gatherMiddleware(),
                ]);
            }

            return $response;
        } catch (Throwable $exception) {
            throw $exception;
        }
    }
}
