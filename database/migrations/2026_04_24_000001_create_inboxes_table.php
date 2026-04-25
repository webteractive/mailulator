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
        Schema::connection($this->getConnection())->create('inboxes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('api_key', 64)->unique();
            $table->unsignedInteger('retention_days')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('inboxes');
    }
};
