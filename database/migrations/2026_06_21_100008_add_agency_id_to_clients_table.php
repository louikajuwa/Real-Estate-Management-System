<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('agency_id')->nullable()->after('id')
                ->constrained('agencies')->nullOnDelete();
            $table->index('agency_id');
        });

        // Backfill existing clients: a client "belongs" to the agency of their
        // assigned agent. Clients with no assigned agent stay null until they
        // engage an agency (inquiry/booking), at which point they're claimed.
        DB::statement('
            UPDATE clients
            SET agency_id = (
                SELECT agents.agency_id FROM agents WHERE agents.id = clients.assigned_agent_id
            )
            WHERE assigned_agent_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropColumn('agency_id');
        });
    }
};
