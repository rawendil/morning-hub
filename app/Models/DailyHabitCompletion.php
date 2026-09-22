<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $routine_block_id
 * @property string $habit_id
 * @property string $local_date
 */
class DailyHabitCompletion extends Model
{
    /** @use HasFactory<\Database\Factories\DailyHabitCompletionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'routine_block_id',
        'habit_id',
        'local_date',
        'completed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
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
