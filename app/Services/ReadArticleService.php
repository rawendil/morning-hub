<?php

namespace App\Services;

use App\Models\ReadArticle;
use App\Models\User;
use Carbon\CarbonImmutable;

class ReadArticleService
{
    public const RETENTION_DAYS = 30;

    /**
     * Links the user marked read within the retention window.
     *
     * @return array<int, string>
     */
    public function readLinksFor(User $user): array
    {
        return ReadArticle::query()
            ->where('user_id', $user->id)
            ->where('read_at', '>=', $this->retentionCutoff())
            ->orderBy('id')
            ->pluck('link')
            ->all();
    }

    public function setRead(User $user, string $link, bool $read): void
    {
        $linkHash = ReadArticle::hashLink($link);

        if (! $read) {
            ReadArticle::query()
                ->where('user_id', $user->id)
                ->where('link_hash', $linkHash)
                ->delete();

            return;
        }

        ReadArticle::query()->updateOrCreate(
            ['user_id' => $user->id, 'link_hash' => $linkHash],
            ['link' => $link, 'read_at' => now()],
        );

        ReadArticle::query()
            ->where('user_id', $user->id)
            ->where('read_at', '<', $this->retentionCutoff())
            ->delete();
    }

    private function retentionCutoff(): CarbonImmutable
    {
        return CarbonImmutable::now()->subDays(self::RETENTION_DAYS);
    }
}
