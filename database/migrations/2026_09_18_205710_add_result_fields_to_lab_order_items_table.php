<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_order_items', function (Blueprint $table) {
            $table->string('result_value', 80)->nullable()->after('line_total');
            $table->string('result_unit', 32)->nullable()->after('result_value');
            $table->string('result_flag', 16)->nullable()->after('result_unit');
            $table->text('result_note')->nullable()->after('result_flag');
        });
    }

    public function down(): void
    {
        Schema::table('lab_order_items', function (Blueprint $table) {
            $table->dropColumn(['result_value', 'result_unit', 'result_flag', 'result_note']);
        });
    }
};
