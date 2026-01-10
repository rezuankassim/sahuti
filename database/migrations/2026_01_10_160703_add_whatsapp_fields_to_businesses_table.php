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
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('waba_id')->nullable()->after('phone_number');
            $table->string('phone_number_id')->nullable()->unique()->after('waba_id');
            $table->string('display_phone_number')->nullable()->after('phone_number_id');
            $table->text('wa_access_token')->nullable()->after('display_phone_number');
            $table->string('webhook_verify_token')->nullable()->after('wa_access_token');
            $table->enum('wa_status', ['pending_connect', 'connected', 'disabled'])->default('pending_connect')->after('webhook_verify_token');
            $table->timestamp('connected_at')->nullable()->after('wa_status');

            $table->index('wa_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['wa_status']);
            $table->dropColumn([
                'waba_id',
                'phone_number_id',
                'display_phone_number',
                'wa_access_token',
                'webhook_verify_token',
                'wa_status',
                'connected_at',
            ]);
        });
    }
};
