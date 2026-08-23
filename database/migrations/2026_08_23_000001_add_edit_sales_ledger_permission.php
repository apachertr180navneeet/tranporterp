<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create 'edit sales ledger' permission if not exists
        $permission = Permission::firstOrCreate([
            'name' => 'edit sales ledger',
            'guard_name' => 'web',
        ]);

        // Assign to Super Admin if role exists
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin && !$superAdmin->hasPermissionTo('edit sales ledger')) {
            $superAdmin->givePermissionTo($permission);
        }

        // Assign to Company Admin if role exists
        $companyAdmin = Role::where('name', 'Company Admin')->first();
        if ($companyAdmin && !$companyAdmin->hasPermissionTo('edit sales ledger')) {
            $companyAdmin->givePermissionTo($permission);
        }

        // Assign to Accountant if role exists
        $accountant = Role::where('name', 'Accountant')->first();
        if ($accountant && !$accountant->hasPermissionTo('edit sales ledger')) {
            $accountant->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::where('name', 'edit sales ledger')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
