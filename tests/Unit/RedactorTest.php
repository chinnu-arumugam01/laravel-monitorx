<?php

namespace Tests\Unit;

use App\Monitoring\Redactor;
use Tests\TestCase;

class RedactorTest extends TestCase
{
    private Redactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new Redactor;
    }

    public function test_redacts_password_field(): void
    {
        $data = ['username' => 'john', 'password' => 'secret123'];
        $result = $this->redactor->redact($data);

        $this->assertEquals('john', $result['username']);
        $this->assertEquals('[REDACTED]', $result['password']);
    }

    public function test_redacts_nested_sensitive_fields(): void
    {
        $data = ['user' => ['password' => 'secret', 'name' => 'John']];
        $result = $this->redactor->redact($data);

        $this->assertEquals('[REDACTED]', $result['user']['password']);
        $this->assertEquals('John', $result['user']['name']);
    }

    public function test_redacts_token_in_key_name(): void
    {
        $data = ['access_token' => 'abc123', 'auth_token' => 'xyz', 'data' => 'safe'];
        $result = $this->redactor->redact($data);

        $this->assertEquals('[REDACTED]', $result['access_token']);
        $this->assertEquals('[REDACTED]', $result['auth_token']);
        $this->assertEquals('safe', $result['data']);
    }

    public function test_redacts_authorization_header(): void
    {
        $data = ['headers' => ['authorization' => 'Bearer secret-token', 'content-type' => 'application/json']];
        $result = $this->redactor->redact($data);

        $this->assertEquals('[REDACTED]', $result['headers']['authorization']);
        $this->assertEquals('application/json', $result['headers']['content-type']);
    }

    public function test_preserves_non_sensitive_data(): void
    {
        $data = ['url' => '/api/users', 'method' => 'GET', 'status' => 200];
        $result = $this->redactor->redact($data);

        $this->assertEquals($data, $result);
    }

    public function test_handles_deeply_nested_arrays(): void
    {
        $data = ['level1' => ['level2' => ['level3' => ['api_key' => 'secret', 'value' => 'safe']]]];
        $result = $this->redactor->redact($data);

        $this->assertEquals('[REDACTED]', $result['level1']['level2']['level3']['api_key']);
        $this->assertEquals('safe', $result['level1']['level2']['level3']['value']);
    }

    public function test_handles_empty_array(): void
    {
        $result = $this->redactor->redact([]);

        $this->assertEquals([], $result);
    }
}
