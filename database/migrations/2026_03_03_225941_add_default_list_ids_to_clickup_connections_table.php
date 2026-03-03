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
        Schema::table('clickup_connections', function (Blueprint $table) {
            $table->json('default_list_ids')->nullable()->after('default_list_id');
        });
    }

    public function down(): void
    {
        Schema::table('clickup_connections', function (Blueprint $table) {
            $table->dropColumn('default_list_ids');
        });
    }
};
