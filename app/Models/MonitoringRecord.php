<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MonitoringRecord extends Model
{
    protected $fillable = [
        'module', 'event', 'status', 'environment', 'application',
        'correlation_id', 'user_id', 'duration_ms', 'summary', 'payload', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function scopeModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeEnvironment(Builder $query, string $environment): Builder
    {
        return $query->where('environment', $environment);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeCorrelated(Builder $query, string $correlationId): Builder
    {
        return $query->where('correlation_id', $correlationId);
    }

    public function scopeFromDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('occurred_at', '>=', $date);
    }

    public function scopeToDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('occurred_at', '<=', $date);
    }

    public function scopeMinDuration(Builder $query, int $ms): Builder
    {
        return $query->where('duration_ms', '>=', $ms);
    }

    public function scopeMaxDuration(Builder $query, int $ms): Builder
    {
        return $query->where('duration_ms', '<=', $ms);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $sub) use ($term) {
            $sub->where('summary', 'like', '%'.$term.'%')
                ->orWhere('correlation_id', 'like', '%'.$term.'%')
                ->orWhere('payload', 'like', '%'.$term.'%');
        });
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'failed', 'denied', 'miss' => 'rose',
            'slow' => 'amber',
            'pending', 'running' => 'sky',
            'allowed', 'success', 'hit', 'submitted' => 'emerald',
            default => 'slate',
        };
    }

    public function isSlow(): bool
    {
        return $this->status === 'slow' || ($this->duration_ms !== null && $this->duration_ms >= config('monitorx.slow_request_ms', 1000));
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function applyFilters(Builder $query, array $filters): Builder
    {
        // Allowlisted simple filters
        if (filled($filters['q'] ?? null)) {
            $query->search($filters['q']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['environment'] ?? null)) {
            $query->where('environment', $filters['environment']);
        }

        if (filled($filters['correlation_id'] ?? null)) {
            $query->where('correlation_id', $filters['correlation_id']);
        }

        if (filled($filters['user_id'] ?? null)) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (filled($filters['from'] ?? null)) {
            $query->whereDate('occurred_at', '>=', $filters['from']);
        }

        if (filled($filters['to'] ?? null)) {
            $query->whereDate('occurred_at', '<=', $filters['to']);
        }

        if (filled($filters['min_duration'] ?? null)) {
            $query->where('duration_ms', '>=', (int) $filters['min_duration']);
        }

        if (filled($filters['max_duration'] ?? null)) {
            $query->where('duration_ms', '<=', (int) $filters['max_duration']);
        }

        // Advanced nested conditions
        if (! empty($filters['conditions'])) {
            self::applyAdvancedConditions($query, $filters['conditions'], $filters['logic'] ?? 'AND');
        }

        return $query;
    }

    /**
     * @param  array<int, array<string, mixed>>  $conditions
     */
    protected static function applyAdvancedConditions(Builder $query, array $conditions, string $logic): void
    {
        $allowedFields = [
            'summary', 'status', 'environment', 'correlation_id',
            'user_id', 'duration_ms', 'module', 'event', 'occurred_at',
        ];

        $operators = ['=', '!=', 'like', 'not like', '>', '<', '>=', '<=', 'between', 'in', 'not in'];

        $method = $logic === 'OR' ? 'orWhere' : 'where';

        $query->$method(function (Builder $sub) use ($conditions, $allowedFields, $operators) {
            foreach ($conditions as $condition) {
                if (isset($condition['conditions'])) {
                    // Nested group
                    $sub->where(function (Builder $nested) use ($condition) {
                        self::applyAdvancedConditions($nested, $condition['conditions'], $condition['logic'] ?? 'AND');
                    });

                    continue;
                }

                $field = $condition['field'] ?? null;
                $operator = strtolower($condition['operator'] ?? '=');
                $value = $condition['value'] ?? null;

                if (! in_array($field, $allowedFields, true) || ! in_array($operator, $operators, true)) {
                    continue;
                }

                match ($operator) {
                    'between' => $sub->whereBetween($field, (array) $value),
                    'in' => $sub->whereIn($field, (array) $value),
                    'not in' => $sub->whereNotIn($field, (array) $value),
                    'like' => $sub->where($field, 'like', '%'.$value.'%'),
                    'not like' => $sub->where($field, 'not like', '%'.$value.'%'),
                    default => $sub->where($field, $operator, $value),
                };
            }
        });
    }

    public static function pruneOlderThan(int $days): int
    {
        return static::where('occurred_at', '<', now()->subDays($days))->delete();
    }
}
