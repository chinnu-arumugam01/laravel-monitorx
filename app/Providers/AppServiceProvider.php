<?php

namespace App\Providers;

use App\Models\MonitoringRecord;
use App\Monitoring\Recorder;
use Illuminate\Bus\Events\BatchDispatched;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Events\RenderedView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        DB::listen(function (QueryExecuted $query): void {
            if (str_contains(strtolower($query->sql), 'monitoring_records')) {
                return;
            }
            app(Recorder::class)->record('queries', 'query', ['summary' => $query->sql, 'sql' => $query->sql,
                'bindings' => $query->bindings, 'duration_ms' => (int) $query->time,
                'status' => $query->time >= config('monitorx.slow_query_ms') ? 'slow' : 'success', 'connection' => $query->connectionName]);
        });
        Event::listen(JobProcessed::class, fn ($event) => app(Recorder::class)->record('jobs', 'processed', [
            'summary' => $event->job->resolveName(), 'job' => $event->job->resolveName(), 'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName, 'status' => 'success', 'attempts' => $event->job->attempts()]));
        Event::listen(JobFailed::class, fn ($event) => app(Recorder::class)->record('jobs', 'failed', [
            'summary' => $event->job->resolveName(), 'job' => $event->job->resolveName(), 'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName, 'status' => 'failed', 'attempts' => $event->job->attempts(),
            'exception' => $event->exception->getMessage()]));
        Event::listen(CommandFinished::class, fn ($event) => app(Recorder::class)->record('commands', 'finished', [
            'summary' => $event->command ?? 'artisan', 'command' => $event->command, 'exit_code' => $event->exitCode,
            'status' => $event->exitCode === 0 ? 'success' : 'failed', 'duration_ms' => (int) ($event->time ?? 0)]));
        Event::listen(ScheduledTaskStarting::class, fn ($event) => app(Recorder::class)->record('schedule', 'starting', [
            'summary' => $event->task->getSummaryForDisplay(), 'task' => $event->task->getSummaryForDisplay(),
            'expression' => $event->task->getExpression(), 'status' => 'running',
        ]));
        Event::listen(ScheduledTaskFinished::class, fn ($event) => app(Recorder::class)->record('schedule', 'finished', [
            'summary' => $event->task->getSummaryForDisplay(), 'task' => $event->task->getSummaryForDisplay(),
            'expression' => $event->task->getExpression(), 'status' => $event->task->exitCode === 0 ? 'success' : 'failed',
            'duration_ms' => (int) ($event->runtime * 1000),
        ]));
        Event::listen(BatchDispatched::class, fn ($event) => app(Recorder::class)->record('batches', 'dispatched', [
            'summary' => $event->batch->name ?: $event->batch->id, 'batch_id' => $event->batch->id,
            'name' => $event->batch->name, 'total_jobs' => $event->batch->totalJobs, 'status' => 'pending',
        ]));
        Event::listen(MessageLogged::class, fn ($event) => app(Recorder::class)->record('logs', 'message', [
            'summary' => $event->message, 'message' => $event->message, 'level' => $event->level,
            'context' => $event->context, 'status' => strtolower($event->level),
        ]));
        Event::listen(CommandExecuted::class, fn ($event) => app(Recorder::class)->record('redis', 'command', [
            'summary' => $event->command, 'command' => $event->command, 'parameters' => $event->parameters,
            'connection' => $event->connectionName, 'duration_ms' => (int) $event->time, 'status' => 'success',
        ]));

        foreach ([
            CacheHit::class => ['cache', 'hit'],
            CacheMissed::class => ['cache', 'miss'],
            KeyWritten::class => ['cache', 'written'],
            KeyForgotten::class => ['cache', 'forgotten'],
        ] as $class => [$module, $kind]) {
            Event::listen($class, fn ($event) => app(Recorder::class)->record($module, $kind, [
                'summary' => $event->key ?? 'cache operation', 'key' => $event->key ?? null,
                'store' => $event->storeName ?? null, 'status' => $kind,
            ]));
        }
        Event::listen(MessageSent::class, fn ($event) => app(Recorder::class)->record('mail', 'submitted', [
            'summary' => 'Mail submitted', 'status' => 'submitted', 'recipients' => array_map(fn ($address) => $address->getAddress(), $event->message->getTo() ?? []),
            'subject' => $event->message->getSubject(), 'transport' => $event->data['__laravel_notification'] ?? null,
        ]));
        Event::listen(RenderedView::class, fn ($event) => app(Recorder::class)->record('views', 'rendered', [
            'summary' => $event->view->name(), 'view' => $event->view->name(), 'status' => 'success',
        ]));
        Event::listen(ResponseReceived::class, fn ($event) => app(Recorder::class)->record('http-client', 'response', [
            'summary' => $event->request->method().' '.$event->request->url(), 'method' => $event->request->method(),
            'url' => $event->request->url(), 'status_code' => $event->response->status(),
            'status' => $event->response->failed() ? 'failed' : 'success',
        ]));
        Event::listen(ConnectionFailed::class, fn ($event) => app(Recorder::class)->record('http-client', 'connection-failed', [
            'summary' => $event->request->method().' '.$event->request->url(), 'status' => 'failed', 'error' => $event->exception->getMessage(),
        ]));
        Event::listen(NotificationSent::class, fn ($event) => app(Recorder::class)->record('notifications', 'sent', [
            'summary' => $event->notification::class, 'notification' => $event->notification::class,
            'channel' => $event->channel, 'notifiable_type' => $event->notifiable::class,
            'notifiable_id' => $event->notifiable->getKey(), 'status' => 'submitted',
        ]));
        Event::listen(NotificationFailed::class, fn ($event) => app(Recorder::class)->record('notifications', 'failed', [
            'summary' => $event->notification::class, 'notification' => $event->notification::class,
            'channel' => $event->channel, 'status' => 'failed', 'error' => ($event->data['exception'] ?? null)?->getMessage(),
        ]));
        Gate::after(function ($user, string $ability, $result, array $arguments): void {
            $model = $arguments[0] ?? null;
            app(Recorder::class)->record('gates', $ability, [
                'summary' => $ability.' · '.($result ? 'allowed' : 'denied'), 'ability' => $ability,
                'user_id' => $user?->getAuthIdentifier(), 'model' => is_object($model) ? $model::class : null,
                'model_id' => $model instanceof Model ? $model->getKey() : null,
                'status' => $result ? 'allowed' : 'denied',
            ]);
        });
        Event::listen('*', function (string $eventName, array $payload): void {
            if (str_starts_with($eventName, 'eloquent.') || str_starts_with($eventName, 'Illuminate\\Database\\') || str_starts_with($eventName, 'Illuminate\\Log\\')) {
                return;
            }
            if (str_starts_with($eventName, 'Illuminate\\Queue\\Events\\') || str_starts_with($eventName, 'Illuminate\\Console\\Events\\') || str_starts_with($eventName, 'Illuminate\\Http\\Client\\Events\\')) {
                return;
            }
            app(Recorder::class)->record('events', $eventName, [
                'summary' => $eventName, 'event' => $eventName,
                'payload_types' => array_map(fn ($item) => is_object($item) ? $item::class : get_debug_type($item), $payload),
            ]);
        });
        Event::listen('eloquent.*', function (string $eventName, array $payload): void {
            $action = explode('.', substr($eventName, strlen('eloquent.')), 2)[0];
            $model = $payload[0] ?? null;
            if (! in_array($action, ['created', 'updated', 'deleted', 'restored', 'retrieved'], true)
                || ! $model instanceof Model || $model instanceof MonitoringRecord) {
                return;
            }

            app(Recorder::class)->record('models', $action, [
                'summary' => $model::class.' #'.$model->getKey(), 'model' => $model::class,
                'model_id' => $model->getKey(), 'changes' => $model->getChanges(), 'original' => $model->getRawOriginal(),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
