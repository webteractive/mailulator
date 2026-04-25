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
        Schema::connection($this->getConnection())->create('emails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('inbox_id')->constrained('inboxes')->cascadeOnDelete();
            $table->string('from');
            $table->json('to');
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('subject')->default('');
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->json('headers');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['inbox_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('emails');
    }
};
