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
        Schema::connection($this->getConnection())->create('attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('email_id')->constrained('emails')->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('disk');
            $table->string('path');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('attachments');
    }
};
