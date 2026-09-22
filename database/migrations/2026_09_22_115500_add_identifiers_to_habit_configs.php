<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Reshape `config.habits` from a plain list of labels into a list of
     * `{id, label}` entries so completion history can reference a habit that
     * survives renaming and reordering.
     */
    public function up(): void
    {
        $blocks = DB::table('routine_blocks')
            ->where('type', 'habits')
            ->whereNotNull('config')
            ->get();

        foreach ($blocks as $block) {
            $config = json_decode($block->config, true);

            if (! is_array($config) || ! isset($config['habits']) || ! is_array($config['habits'])) {
                continue;
            }

            $config['habits'] = array_values(array_map(
                fn ($habit) => is_array($habit)
                    ? ['id' => $habit['id'] ?? (string) Str::uuid(), 'label' => $habit['label'] ?? '']
                    : ['id' => (string) Str::uuid(), 'label' => (string) $habit],
                $config['habits'],
            ));

            DB::table('routine_blocks')
                ->where('id', $block->id)
                ->update(['config' => json_encode($config)]);
        }
    }

    public function down(): void
    {
        $blocks = DB::table('routine_blocks')
            ->where('type', 'habits')
            ->whereNotNull('config')
            ->get();

        foreach ($blocks as $block) {
            $config = json_decode($block->config, true);

            if (! is_array($config) || ! isset($config['habits']) || ! is_array($config['habits'])) {
                continue;
            }

            $config['habits'] = array_values(array_map(
                fn ($habit) => is_array($habit) ? ($habit['label'] ?? '') : (string) $habit,
                $config['habits'],
            ));

            DB::table('routine_blocks')
                ->where('id', $block->id)
                ->update(['config' => json_encode($config)]);
        }
    }
};
