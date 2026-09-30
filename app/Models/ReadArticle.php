<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadArticle extends Model
{
    /** @use HasFactory<\Database\Factories\ReadArticleFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'link',
        'link_hash',
        'read_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'read_at' => 'immutable_datetime',
        ];
    }

    public static function hashLink(string $link): string
    {
        return hash('sha256', $link);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
