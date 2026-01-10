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
        Schema::create('auto_reply_logs', function (Blueprint $table) {
            $table->id();
            $table->string('customer_phone')->index();
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('message_text');
            $table->text('reply_text')->nullable();
            $table->string('reply_type'); // rule, llm, fallback, after_hours, rate_limited
            $table->integer('llm_tokens_used')->nullable();
            $table->boolean('rate_limited')->default(false);
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'created_at']);
            $table->index(['customer_phone', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_reply_logs');
    }
};
