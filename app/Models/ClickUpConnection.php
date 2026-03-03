<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClickUpConnection extends Model
{
    /** @use HasFactory<\Database\Factories\ClickUpConnectionFactory> */
    use HasFactory;

    protected $table = 'clickup_connections';

    protected $fillable = [
        'name',
        'api_token',
        'workspace_id',
        'default_space_id',
        'default_folder_id',
        'default_list_id',
        'default_list_ids',
        'default_filters',
    ];

    protected $hidden = [
        'api_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'default_list_ids' => 'array',
            'default_filters' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
