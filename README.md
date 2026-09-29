# MonitorX

MonitorX is a Laravel monitoring dashboard foundation built on a shared, indexed record store. It includes request, database query, queue job, command, exception, cache, mail, HTTP client, notification, Eloquent model, gate, and event hooks, plus a dashboard, module lists, record detail pages, correlation timelines, server-side search/filtering, CSV export, and retention pruning.

## Setup

Requirements: PHP 8.3+, Composer, and a Laravel-supported database. Install the project dependencies and configure `.env`:

```sh
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

Open `/monitorx`. In non-local environments, configure `MONITORX_USERNAME` and `MONITORX_PASSWORD` to protect the dashboard with HTTP Basic authentication. Local and test environments are accessible without credentials.

Useful settings:

```dotenv
MONITORX_ENABLED=true
MONITORX_PATH=monitorx
MONITORX_USERNAME=monitoring-user
MONITORX_PASSWORD=replace-with-a-long-secret
MONITORX_RETENTION_DAYS=30
MONITORX_SLOW_REQUEST_MS=1000
MONITORX_SLOW_QUERY_MS=100
MONITORX_DUMPS_ENABLED=false
```

Schedule Laravel's task runner (`php artisan schedule:work` locally or a cron entry in production) to run the daily `monitorx:prune` task. Use `app(App\Monitoring\Recorder::class)->record($module, $event, $data)` to add application-specific collection hooks. Only configured module keys are accepted. Sensitive payload keys matching the configured redaction words are recursively replaced before persistence.

## Current scope

The 18 module routes share a production-oriented record model and query interface. Collectors are connected for the framework hooks listed above; some module-specific instrumentation still needs dedicated integrations (notably schedule lifecycle, Redis commands, dump interception, full log aggregation, query explain plans, and batch lifecycle). The filter form currently supports keyword, status, and date bounds; nested AND/OR groups, saved presets, sortable/visible columns, and chart analytics are not implemented yet. The dashboard presents database-backed counts and recent activity rather than fixture data.

Authentication uses deployment-configured Basic Auth for the monitoring surface. Configure transport security and credentials in the hosting environment before exposing it outside local development.
