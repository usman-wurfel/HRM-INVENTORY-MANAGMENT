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
        Schema::table('essentials_holidays', function (Blueprint $table) {
            $table->enum('repeat_pattern', ['every', 'alternate', 'custom'])->nullable()->after('repeat_type'); // every week, alternate week, custom dates
            $table->integer('gap_weeks')->nullable()->default(1)->after('repeat_pattern'); // Gap after how many weeks (for alternate pattern)
            $table->text('custom_dates')->nullable()->after('gap_weeks'); // JSON array of custom dates
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('essentials_holidays', function (Blueprint $table) {
            $table->dropColumn(['repeat_pattern', 'gap_weeks', 'custom_dates']);
        });
    }
};

