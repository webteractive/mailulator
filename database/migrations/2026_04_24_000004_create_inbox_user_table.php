<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mailulator';

    public function up(): void
    {
        Schema::connection($this->connection)->create('inbox_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('inbox_id')->constrained('inboxes')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['inbox_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('inbox_user');
    }
};
