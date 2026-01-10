<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('from');
            $table->string('to');
            $table->string('message_type')->default('text');
            $table->json('content');
            $table->string('status')->default('sent');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['direction', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
