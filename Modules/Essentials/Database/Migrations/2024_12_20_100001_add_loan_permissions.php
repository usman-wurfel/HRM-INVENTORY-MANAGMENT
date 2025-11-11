<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class AddLoanPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissions = [
            'essentials.loan_request',
            'essentials.loan_manage',
        ];

        foreach ($permissions as $permission) {
            $existing = Permission::where('name', $permission)->first();
            if (!$existing) {
                Permission::create(['name' => $permission, 'guard_name' => 'web']);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::whereIn('name', ['essentials.loan_request', 'essentials.loan_manage'])->delete();
    }
}

