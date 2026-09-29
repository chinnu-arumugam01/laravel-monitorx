<?php

namespace Tests\Feature;

use App\Models\MonitoringRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed some records
        MonitoringRecord::create(['module' => 'requests', 'event' => 'request', 'status' => 'success', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'GET /home', 'duration_ms' => 50, 'occurred_at' => now()]);
        MonitoringRecord::create(['module' => 'requests', 'event' => 'request', 'status' => 'failed', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'GET /error', 'duration_ms' => 1200, 'occurred_at' => now()]);
        MonitoringRecord::create(['module' => 'requests', 'event' => 'request', 'status' => 'slow', 'environment' => 'production', 'application' => 'TestApp', 'summary' => 'GET /slow', 'duration_ms' => 2000, 'occurred_at' => now()]);
        MonitoringRecord::create(['module' => 'jobs', 'event' => 'processed', 'status' => 'success', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'SendEmail', 'occurred_at' => now()]);
        MonitoringRecord::create(['module' => 'exceptions', 'event' => 'RuntimeException', 'status' => 'failed', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'Something went wrong', 'occurred_at' => now()]);
    }

    public function test_filter_by_status(): void
    {
        $results = MonitoringRecord::applyFilters(
            MonitoringRecord::query()->module('requests'),
            ['status' => 'success']
        )->get();

        $this->assertCount(1, $results);
        $this->assertEquals('GET /home', $results->first()->summary);
    }

    public function test_filter_by_environment(): void
    {
        $results = MonitoringRecord::applyFilters(
            MonitoringRecord::query()->module('requests'),
            ['environment' => 'production']
        )->get();

        $this->assertCount(1, $results);
        $this->assertEquals('GET /slow', $results->first()->summary);
    }

    public function test_filter_by_search_term(): void
    {
        $results = MonitoringRecord::applyFilters(
            MonitoringRecord::query()->module('requests'),
            ['q' => 'error']
        )->get();

        $this->assertCount(1, $results);
        $this->assertEquals('GET /error', $results->first()->summary);
    }

    public function test_filter_by_min_duration(): void
    {
        $results = MonitoringRecord::applyFilters(
            MonitoringRecord::query()->module('requests'),
            ['min_duration' => 1000]
        )->get();

        $this->assertCount(2, $results); // failed (1200) and slow (2000)
    }

    public function test_filter_by_max_duration(): void
    {
        $results = MonitoringRecord::applyFilters(
            MonitoringRecord::query()->module('requests'),
            ['max_duration' => 100]
        )->get();

        $this->assertCount(1, $results);
        $this->assertEquals('GET /home', $results->first()->summary);
    }

    public function test_filter_by_date_range(): void
    {
        MonitoringRecord::create(['module' => 'logs', 'event' => 'message', 'status' => 'info', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'Old log', 'occurred_at' => now()->subDays(10)]);

        $results = MonitoringRecord::applyFilters(
            MonitoringRecord::query()->module('logs'),
            ['from' => now()->subDays(1)->format('Y-m-d'), 'to' => now()->format('Y-m-d')]
        )->get();

        $this->assertCount(0, $results); // No logs in recent date range (old one is 10 days ago)
    }

    public function test_advanced_conditions_with_equals_operator(): void
    {
        $results = MonitoringRecord::applyFilters(
            MonitoringRecord::query()->module('requests'),
            ['conditions' => [['field' => 'status', 'operator' => '=', 'value' => 'failed']]]
        )->get();

        $this->assertCount(1, $results);
        $this->assertEquals('failed', $results->first()->status);
    }

    public function test_advanced_conditions_blocks_non_allowlisted_field(): void
    {
        // Should not throw, just ignore the non-allowlisted field
        $results = MonitoringRecord::applyFilters(
            MonitoringRecord::query()->module('requests'),
            ['conditions' => [['field' => 'DROP TABLE monitoring_records; --', 'operator' => '=', 'value' => '1']]]
        )->get();

        // Should return all records (malicious condition ignored)
        $this->assertCount(3, $results);
    }

    public function test_module_scope(): void
    {
        $requests = MonitoringRecord::module('requests')->count();
        $jobs = MonitoringRecord::module('jobs')->count();
        $exceptions = MonitoringRecord::module('exceptions')->count();

        $this->assertEquals(3, $requests);
        $this->assertEquals(1, $jobs);
        $this->assertEquals(1, $exceptions);
    }
}
