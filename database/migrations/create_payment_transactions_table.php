<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the table for new installs, or adds payable columns when the table already exists (e.g. from app migration with order_id).
     */
    public function up(): void
    {
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->morphs('payable');
                $table->string('track_id')->unique();
                $table->string('payment_id')->nullable();
                $table->decimal('amount', 10, 2)->nullable();
                $table->string('status')->default('pending');
                $table->string('payment_gateway')->nullable();
                $table->json('response')->nullable();
                $table->json('gateway_response')->nullable();
                $table->timestamps();
            });
            return;
        }

        if (!Schema::hasColumn('payment_transactions', 'payable_type')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->string('payable_type')->nullable()->after('id');
                $table->unsignedBigInteger('payable_id')->nullable()->after('payable_type');
            });
        }
        if (!Schema::hasColumn('payment_transactions', 'gateway_response')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->json('gateway_response')->nullable()->after('response');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('payment_transactions')) {
            return;
        }
        if (Schema::hasColumn('payment_transactions', 'payable_type')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->dropColumn(['payable_type', 'payable_id']);
            });
        }
        if (Schema::hasColumn('payment_transactions', 'gateway_response')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->dropColumn('gateway_response');
            });
        }
    }
};
