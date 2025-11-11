<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission = 'essentials.crud_holiday';
        
        $existing = Permission::where('name', $permission)->first();
        if (!$existing) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'essentials.crud_holiday')->delete();
    }
};

