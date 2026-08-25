<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id()->generatedAs();
            $table->string('level', 10);
            $table->text('message');
            $table->string('source')->nullable();
            $table->jsonb('context')->nullable();
            $table->timestampTz('occurred_at', 3);
            $table->timestampTz('created_at', 3)->useCurrent();

            $table->index(['occurred_at', 'id'], 'events_occurred_at_id_idx');
            $table->index(['level', 'occurred_at', 'id'], 'events_level_occurred_at_id_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE events ADD CONSTRAINT events_level_check CHECK (level IN ('debug', 'info', 'warning', 'error', 'critical'))");
            DB::statement('ALTER TABLE events ADD CONSTRAINT events_message_length_check CHECK (char_length(message) BETWEEN 1 AND 2000)');
            DB::statement("ALTER TABLE events ADD CONSTRAINT events_context_object_check CHECK (context IS NULL OR jsonb_typeof(context) = 'object')");
            DB::statement('ALTER TABLE events ADD CONSTRAINT events_context_size_check CHECK (context IS NULL OR octet_length(context::text) <= 65536)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
