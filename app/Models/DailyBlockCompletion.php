<?php

namespace App\Models;

use App\Enums\BlockCompletionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $routine_block_id
 * @property string $local_date
 * @property BlockCompletionStatus $status
 * @property int|null $elapsed_seconds
 */
class DailyBlockCompletion extends Model
{
    /** @use HasFactory<\Database\Factories\DailyBlockCompletionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'routine_block_id',
        'local_date',
        'status',
        'elapsed_seconds',
        'completed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => BlockCompletionStatus::class,
            'elapsed_seconds' => 'integer',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<RoutineBlock, $this> */
    public function routineBlock(): BelongsTo
    {
        return $this->belongsTo(RoutineBlock::class);
    }
}
