<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mailulator';

    public function up(): void
    {
        Schema::connection('mailulator')->table('inboxes', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('retention_days');
        });
    }

    public function down(): void
    {
        Schema::connection('mailulator')->table('inboxes', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
