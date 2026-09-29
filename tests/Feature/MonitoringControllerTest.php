<?php

namespace Tests\Feature;

use App\Models\MonitoringRecord;
use App\Models\SavedFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable monitoring during tests to avoid feedback loops
        config(['monitorx.enabled' => false]);
    }

    public function test_dashboard_is_accessible(): void
    {
        $response = $this->get('/monitorx');

        $response->assertStatus(200);
        $response->assertViewIs('monitorx.dashboard');
    }

    public function test_module_index_is_accessible(): void
    {
        $response = $this->get('/monitorx/requests');

        $response->assertStatus(200);
        $response->assertViewIs('monitorx.index');
        $response->assertViewHas('module', 'requests');
    }

    public function test_unknown_module_returns_404(): void
    {
        $response = $this->get('/monitorx/nonexistent-module');

        $response->assertStatus(404);
    }

    public function test_module_index_shows_records(): void
    {
        MonitoringRecord::create(['module' => 'requests', 'event' => 'request', 'status' => 'success', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'GET /test', 'occurred_at' => now()]);

        $response = $this->get('/monitorx/requests');

        $response->assertStatus(200);
        $response->assertSee('GET /test');
    }

    public function test_module_index_filter_by_status(): void
    {
        MonitoringRecord::create(['module' => 'requests', 'event' => 'request', 'status' => 'success', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'GET /ok', 'occurred_at' => now()]);
        MonitoringRecord::create(['module' => 'requests', 'event' => 'request', 'status' => 'failed', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'GET /fail', 'occurred_at' => now()]);

        $response = $this->get('/monitorx/requests?status=success');

        $response->assertStatus(200);
        $response->assertSee('GET /ok');
        $response->assertDontSee('GET /fail');
    }

    public function test_record_detail_is_accessible(): void
    {
        $record = MonitoringRecord::create(['module' => 'requests', 'event' => 'request', 'status' => 'success', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'GET /detail', 'occurred_at' => now()]);

        $response = $this->get("/monitorx/requests/{$record->id}");

        $response->assertStatus(200);
        $response->assertViewIs('monitorx.show');
        $response->assertSee('GET /detail');
    }

    public function test_record_detail_wrong_module_returns_404(): void
    {
        $record = MonitoringRecord::create(['module' => 'requests', 'event' => 'request', 'status' => 'success', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'GET /test', 'occurred_at' => now()]);

        $response = $this->get("/monitorx/jobs/{$record->id}");

        $response->assertStatus(404);
    }

    public function test_global_search_returns_results(): void
    {
        MonitoringRecord::create(['module' => 'exceptions', 'event' => 'RuntimeException', 'status' => 'failed', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'Connection refused', 'occurred_at' => now()]);

        $response = $this->get('/monitorx/search?q=Connection');

        $response->assertStatus(200);
        $response->assertSee('Connection refused');
    }

    public function test_csv_export_returns_csv_response(): void
    {
        MonitoringRecord::create(['module' => 'requests', 'event' => 'request', 'status' => 'success', 'environment' => 'local', 'application' => 'TestApp', 'summary' => 'GET /csv-test', 'occurred_at' => now()]);

        $response = $this->get('/monitorx/requests/export');

        $response->assertStatus(200);
        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('GET /csv-test', $response->streamedContent());
    }

    public function test_saved_filters_index_is_accessible(): void
    {
        $response = $this->get('/monitorx/saved-filters');

        $response->assertStatus(200);
        $response->assertViewIs('monitorx.saved-filters');
    }

    public function test_saved_filter_can_be_created(): void
    {
        $response = $this->post('/monitorx/saved-filters', [
            'name' => 'My Failed Requests',
            'module' => 'requests',
            'conditions' => json_encode([['field' => 'status', 'operator' => '=', 'value' => 'failed']]),
            'logic' => 'AND',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('saved_filters', ['name' => 'My Failed Requests', 'module' => 'requests']);
    }

    public function test_saved_filter_can_be_deleted(): void
    {
        $filter = SavedFilter::create(['name' => 'Delete Me', 'module' => 'jobs', 'conditions' => [], 'logic' => 'AND']);

        $response = $this->delete("/monitorx/saved-filters/{$filter->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('saved_filters', ['id' => $filter->id]);
    }

    public function test_settings_page_is_accessible(): void
    {
        $response = $this->get('/monitorx/settings');

        $response->assertStatus(200);
        $response->assertViewIs('monitorx.settings');
    }
}
