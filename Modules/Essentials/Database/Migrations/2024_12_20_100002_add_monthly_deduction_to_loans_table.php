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
            $table->decimal('monthly_deduction', 22, 4)->nullable()->after('loan_amount');
            $table->decimal('total_deduction_paid', 22, 4)->default(0)->after('monthly_deduction');
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
            $table->dropColumn(['monthly_deduction', 'total_deduction_paid']);
        });
    }
};

