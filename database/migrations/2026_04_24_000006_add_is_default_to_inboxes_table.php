<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mailulator';

    public function up(): void
    {
        Schema::connection('mailulator')->table('inboxes', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('retention_days');
        });

        DB::connection('mailulator')
            ->table('inboxes')
            ->where('name', 'Default')
            ->update(['is_default' => true]);
    }

    public function down(): void
    {
        Schema::connection('mailulator')->table('inboxes', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
