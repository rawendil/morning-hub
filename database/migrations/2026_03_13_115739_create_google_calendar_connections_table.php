<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('google_calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('google_id');
            $table->string('name');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at');
            $table->json('calendar_ids')->nullable();
            $table->timestamps();
        });

        Schema::table('routine_blocks', function (Blueprint $table) {
            $table->foreignId('google_calendar_connection_id')
                ->nullable()
                ->after('clickup_connection_id')
                ->constrained('google_calendar_connections')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routine_blocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('google_calendar_connection_id');
        });

        Schema::dropIfExists('google_calendar_connections');
    }
};
