<?php

use App\Http\Middleware\RecordMonitoredRequest;
use App\Monitoring\Recorder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(RecordMonitoredRequest::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $exception): void {
            app(Recorder::class)->record('exceptions', $exception::class, [
                'summary' => $exception->getMessage(), 'exception' => $exception::class, 'message' => $exception->getMessage(),
                'file' => $exception->getFile(), 'line' => $exception->getLine(), 'trace' => $exception->getTraceAsString(), 'status' => 'failed',
            ]);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
