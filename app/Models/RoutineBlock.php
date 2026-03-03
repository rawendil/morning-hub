<?php

namespace App\Models;

use App\Enums\BlockType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutineBlock extends Model
{
    /** @use HasFactory<\Database\Factories\RoutineBlockFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'sort_order',
        'timer_minutes',
        'clickup_connection_id',
        'config',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => BlockType::class,
            'config' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ClickUpConnection, $this> */
    public function clickUpConnection(): BelongsTo
    {
        return $this->belongsTo(ClickUpConnection::class, 'clickup_connection_id');
    }

    /** @param Builder<RoutineBlock> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order');
    }
}
