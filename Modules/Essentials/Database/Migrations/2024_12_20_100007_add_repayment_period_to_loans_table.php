<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('essentials_loans', function (Blueprint $table) {
            $table->string('repayment_period')->nullable()->after('total_deduction_paid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('essentials_loans', function (Blueprint $table) {
            $table->dropColumn('repayment_period');
        });
    }
};

