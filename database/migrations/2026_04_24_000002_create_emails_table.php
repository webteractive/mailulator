<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mailulator';

    public function up(): void
    {
        Schema::connection($this->connection)->create('emails', function (Blueprint $table) {
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
        Schema::connection($this->connection)->dropIfExists('emails');
    }
};
