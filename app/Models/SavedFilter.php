<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedFilter extends Model
{
    protected $fillable = ['name', 'module', 'conditions', 'logic', 'is_shared', 'user_id'];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'is_shared' => 'boolean',
        ];
    }
}
