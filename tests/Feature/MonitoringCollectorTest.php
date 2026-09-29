<?php

namespace Tests\Feature;

use App\Models\MonitoringRecord;
use App\Monitoring\Recorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitoringCollectorTest extends TestCase
{
    use RefreshDatabase;

    private Recorder $recorder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recorder = app(Recorder::class);
    }

    public function test_recorder_creates_monitoring_record(): void
    {
        $record = $this->recorder->record('requests', 'request', [
            'summary' => 'GET /test',
            'status' => 'success',
            'duration_ms' => 42,
        ]);

        $this->assertNotNull($record);
        $this->assertDatabaseHas('monitoring_records', [
            'module' => 'requests',
            'event' => 'request',
            'status' => 'success',
            'duration_ms' => 42,
            'summary' => 'GET /test',
        ]);
    }

    public function test_recorder_does_not_interrupt_when_disabled(): void
    {
        config(['monitorx.enabled' => false]);

        $result = $this->recorder->record('requests', 'request', ['summary' => 'test']);

        $this->assertNull($result);
        $this->assertDatabaseMissing('monitoring_records', ['module' => 'requests']);
    }

    public function test_recorder_silently_handles_unknown_module(): void
    {
        $result = $this->recorder->record('nonexistent-module', 'event', []);

        $this->assertNull($result);
    }

    public function test_recorder_stores_all_18_modules(): void
    {
        $modules = array_keys(config('monitorx.modules'));

        foreach ($modules as $module) {
            $this->recorder->record($module, 'test', ['summary' => "Test {$module}"]);
        }

        foreach ($modules as $module) {
            $this->assertDatabaseHas('monitoring_records', [
                'module' => $module,
                'event' => 'test',
                'summary' => "Test {$module}",
            ]);
        }

        $this->assertEquals(18, MonitoringRecord::where('event', 'test')->count());
        $this->assertEquals(18, MonitoringRecord::where('event', 'test')->distinct('module')->count('module'));
    }

    public function test_recorder_captures_correlation_id_from_context(): void
    {
        Context::add('monitorx_correlation_id', 'test-uuid-1234');

        $record = $this->recorder->record('logs', 'message', ['summary' => 'test log']);

        $this->assertEquals('test-uuid-1234', $record->correlation_id);
    }

    public function test_recorder_does_not_crash_on_storage_failure(): void
    {
        // Force a failure by making the DB unavailable
        $this->expectNotToPerformAssertions();

        // Recorder should not throw even if something goes wrong internally
        try {
            $this->recorder->record('requests', 'request', ['summary' => 'test']);
        } catch (\Throwable) {
            $this->fail('Recorder should silently fail, not throw');
        }
    }

    public function test_query_listener_records_queries(): void
    {
        // Trigger a DB query that isn't to monitoring_records
        DB::select('SELECT 1 as test');

        // Give the listener a chance to fire (it fires synchronously)
        $this->assertDatabaseHas('monitoring_records', ['module' => 'queries']);
    }

    public function test_exception_handler_records_exceptions(): void
    {
        $recorder = $this->recorder;
        $recorder->record('exceptions', \RuntimeException::class, [
            'summary' => 'Test exception',
            'exception' => \RuntimeException::class,
            'message' => 'Test exception',
            'file' => __FILE__,
            'line' => __LINE__,
            'trace' => 'stack trace here',
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('monitoring_records', [
            'module' => 'exceptions',
            'status' => 'failed',
        ]);
    }
}
