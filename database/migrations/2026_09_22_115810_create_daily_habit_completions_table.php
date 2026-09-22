<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_habit_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routine_block_id')->constrained()->cascadeOnDelete();
            $table->string('habit_id', 64);
            $table->date('local_date');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['user_id', 'habit_id', 'local_date']);
            $table->index(['user_id', 'local_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_habit_completions');
    }
};
