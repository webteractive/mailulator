<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return config('mailulator.receiver.database.connection', 'mailulator');
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->table('inboxes', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('retention_days');
        });

        DB::connection($this->getConnection())
            ->table('inboxes')
            ->where('name', 'Default')
            ->update(['is_default' => true]);
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->table('inboxes', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
