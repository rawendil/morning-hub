<?php

namespace App\Models;

use Database\Factories\TodaysTasksConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TodaysTasksConfig extends Model
{
    /** @use HasFactory<TodaysTasksConfigFactory> */
    use HasFactory;

    protected $fillable = [
        'connection_ids',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'connection_ids' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
