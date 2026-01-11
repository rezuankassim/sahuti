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
            $table->string('meta_app_id')->nullable()->after('webhook_verify_token');
            $table->text('meta_app_secret')->nullable()->after('meta_app_id');
            $table->string('onboarding_phone')->nullable()->after('meta_app_secret');

            $table->index('onboarding_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['onboarding_phone']);
            $table->dropColumn([
                'meta_app_id',
                'meta_app_secret',
                'onboarding_phone',
            ]);
        });
    }
};
