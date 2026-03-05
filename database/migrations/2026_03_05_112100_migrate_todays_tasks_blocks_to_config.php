<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $blocks = DB::table('routine_blocks')
            ->where('type', 'todays_tasks')
            ->get();

        $grouped = $blocks->groupBy('user_id');

        foreach ($grouped as $userId => $userBlocks) {
            $allConnectionIds = $userBlocks
                ->pluck('config')
                ->map(fn ($config) => json_decode($config, true)['connection_ids'] ?? [])
                ->flatten()
                ->unique()
                ->values()
                ->all();

            DB::table('todays_tasks_configs')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'connection_ids' => json_encode($allConnectionIds),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('routine_blocks')
            ->where('type', 'todays_tasks')
            ->delete();
    }

    public function down(): void
    {
        // Data migration — not reversible
    }
};
