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
            $table->enum('type', ['normal', 'consecutive'])->default('normal')->after('name');
            $table->integer('user_id')->nullable()->index()->after('type');
            $table->string('weekdays')->nullable()->after('user_id'); // Comma separated: 0,6 for Sunday, Saturday
            $table->enum('repeat_type', ['week', 'month'])->nullable()->after('weekdays');
            $table->string('repeat_days')->nullable()->after('repeat_type'); // For month: comma separated days like 1,15,30
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
            $table->dropColumn(['type', 'user_id', 'weekdays', 'repeat_type', 'repeat_days']);
        });
    }
};

