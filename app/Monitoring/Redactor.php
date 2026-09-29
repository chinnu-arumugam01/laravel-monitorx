<?php

namespace App\Monitoring;

class Redactor
{
    /** @var list<string> */
    private array $keys;

    public function __construct()
    {
        $this->keys = array_map(
            'strtolower',
            config('monitorx.redact', ['password', 'token', 'secret', 'authorization', 'cookie', 'api_key'])
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function redact(array $data): array
    {
        return $this->redactValue($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function redactValue(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $result = [];

        foreach ($data as $key => $value) {
            if ($this->shouldRedactKey((string) $key)) {
                $result[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $result[$key] = $this->redactValue($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function shouldRedactKey(string $key): bool
    {
        $lowerKey = strtolower($key);

        foreach ($this->keys as $redactedKey) {
            if (str_contains($lowerKey, $redactedKey)) {
                return true;
            }
        }

        return false;
    }
}
