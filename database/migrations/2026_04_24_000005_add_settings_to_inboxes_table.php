<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->json('settings')->nullable()->after('retention_days');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->table('inboxes', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
