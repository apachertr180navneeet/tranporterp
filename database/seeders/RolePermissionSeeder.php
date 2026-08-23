<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Complete list of permissions for all modules in the application
        $permissions = [
            'companies' => [
                'view companies', 'create companies', 'edit companies', 'delete companies', 'restore companies', 'force delete companies'
            ],
            'branches' => [
                'view branches', 'create branches', 'edit branches', 'delete branches', 'restore branches', 'force delete branches'
            ],
            'users' => [
                'view users', 'create users', 'edit users', 'delete users'
            ],
            'roles' => [
                'view roles', 'create roles', 'edit roles', 'delete roles'
            ],
            'permissions' => [
                'view permissions', 'create permissions', 'edit permissions', 'delete permissions'
            ],
            'bulties' => [
                'view bulties', 'create bulties', 'edit bulties', 'delete bulties', 'restore bulties', 'force delete bulties', 'cancel bulties', 'print bulties', 'approve bulty documents', 'approve bulty pod'
            ],
            'trips' => [
                'view trips', 'create trips', 'edit trips', 'delete trips', 'close trips', 'import trip data'
            ],
            'billing' => [
                'view billing', 'create billing', 'edit billing', 'delete billing', 'view invoices', 'create invoices', 'edit invoices', 'delete invoices', 'print invoices', 'export invoices'
            ],
            'toll_bills' => [
                'view toll bills', 'create toll bills', 'edit toll bills', 'delete toll bills'
            ],
            'letterheads' => [
                'view letterheads', 'create letterheads', 'edit letterheads', 'delete letterheads', 'send letterheads mail'
            ],
            'driver_salary' => [
                'view driver salary', 'create driver salary', 'edit driver salary', 'delete driver salary',
                'view driver advances', 'create driver advances', 'edit driver advances', 'delete driver advances',
                'generate driver salary slips', 'view driver salary slips', 'delete driver salary slips'
            ],
            'employee_salary' => [
                'view employee salary', 'create employee salary', 'edit employee salary', 'delete employee salary',
                'view attendance', 'mark attendance',
                'view leaves', 'create leaves', 'approve leaves', 'reject leaves', 'delete leaves',
                'view employee advances', 'create employee advances', 'approve employee advances', 'reject employee advances', 'mark employee advances paid', 'delete employee advances'
            ],
            'loans' => [
                'view company loans', 'create company loans', 'edit company loans', 'delete company loans', 'record company loan payments',
                'view vehicle loans', 'create vehicle loans', 'edit vehicle loans', 'delete vehicle loans'
            ],
            'maintenance' => [
                'view service schedules', 'create service schedules', 'edit service schedules', 'delete service schedules', 'restore service schedules', 'force delete service schedules', 'mark service schedules completed',
                'view spare parts', 'create spare parts', 'edit spare parts', 'delete spare parts', 'restore spare parts', 'force delete spare parts',
                'view maintenance history', 'create maintenance history', 'edit maintenance history', 'delete maintenance history', 'restore maintenance history', 'force delete maintenance history',
                'view breakdowns', 'create breakdowns', 'edit breakdowns', 'delete breakdowns', 'restore breakdowns', 'force delete breakdowns', 'mark breakdowns resolved',
                'view tyre management', 'create tyre management', 'edit tyre management', 'delete tyre management', 'restore tyre management', 'force delete tyre management'
            ],
            'reports' => [
                'view reports', 'export reports',
                'view vehicle report', 'view driver trip report', 'view customer ledger', 'view sales ledger', 'edit sales ledger', 'view tds report',
                'view trip reports', 'view bilty advance details', 'view fuel report', 'view adblue report',
                'view vehicle utilization', 'view mis report', 'view expense management', 'view vehicle document report',
                'view gst tax report', 'view profit loss report'
            ],
            'fuel_outstanding' => [
                'view fuel outstanding', 'create fuel outstanding', 'edit fuel outstanding', 'delete fuel outstanding'
            ],
            'adblue_outstanding' => [
                'view adblue outstanding', 'create adblue outstanding', 'edit adblue outstanding', 'delete adblue outstanding'
            ],
            'consignors' => [
                'view consignors', 'create consignors', 'edit consignors', 'delete consignors', 'restore consignors', 'force delete consignors', 'transfer consignors', 'import consignors'
            ],
            'consignees' => [
                'view consignees', 'create consignees', 'edit consignees', 'delete consignees', 'restore consignees', 'force delete consignees', 'transfer consignees', 'import consignees'
            ],
            'vehicles' => [
                'view vehicles', 'create vehicles', 'edit vehicles', 'delete vehicles', 'restore vehicles', 'force delete vehicles', 'import vehicles', 'export vehicles'
            ],
            'drivers' => [
                'view drivers', 'create drivers', 'edit drivers', 'delete drivers', 'restore drivers', 'force delete drivers', 'import drivers', 'export drivers'
            ],
            'gst' => [
                'view gst', 'create gst', 'edit gst', 'delete gst', 'restore gst', 'force delete gst', 'import gst'
            ],
            'cities' => [
                'view cities', 'create cities', 'edit cities', 'delete cities', 'restore cities', 'force delete cities', 'import cities'
            ],
            'packagings' => [
                'view packagings', 'create packagings', 'edit packagings', 'delete packagings', 'restore packagings', 'force delete packagings', 'import packagings'
            ],
            'units' => [
                'view units', 'create units', 'edit units', 'delete units', 'restore units', 'force delete units', 'import units'
            ],
            'fuel_pumps' => [
                'view fuel pumps', 'create fuel pumps', 'edit fuel pumps', 'delete fuel pumps', 'restore fuel pumps', 'force delete fuel pumps', 'import fuel pumps'
            ],
            'fuel_companies' => [
                'view fuel companies', 'create fuel companies', 'edit fuel companies', 'delete fuel companies', 'restore fuel companies', 'force delete fuel companies', 'import fuel companies'
            ],
            'adblue_companies' => [
                'view adblue companies', 'create adblue companies', 'edit adblue companies', 'delete adblue companies', 'restore adblue companies', 'force delete adblue companies', 'import adblue companies'
            ],
            'tyre_brands' => [
                'view tyre brands', 'create tyre brands', 'edit tyre brands', 'delete tyre brands', 'restore tyre brands', 'force delete tyre brands'
            ],
            'tyre_models' => [
                'view tyre models', 'create tyre models', 'edit tyre models', 'delete tyre models', 'restore tyre models', 'force delete tyre models'
            ],
            'tyre_sizes' => [
                'view tyre sizes', 'create tyre sizes', 'edit tyre sizes', 'delete tyre sizes', 'restore tyre sizes', 'force delete tyre sizes'
            ],
            'items' => [
                'view items', 'create items', 'edit items', 'delete items', 'restore items', 'force delete items', 'import items'
            ],
            'suppliers' => [
                'view suppliers', 'create suppliers', 'edit suppliers', 'delete suppliers', 'restore suppliers', 'force delete suppliers', 'import suppliers'
            ],
            'vendors' => [
                'view vendors', 'create vendors', 'edit vendors', 'delete vendors', 'restore vendors', 'force delete vendors', 'import vendors'
            ],
            'banks' => [
                'view banks', 'create banks', 'edit banks', 'delete banks', 'restore banks', 'force delete banks', 'import banks'
            ],
            'bank_branches' => [
                'view bank branches', 'create bank branches', 'edit bank branches', 'delete bank branches', 'restore bank branches', 'force delete bank branches', 'import bank branches'
            ],
            'bill_formats' => [
                'view bill formats', 'create bill formats', 'edit bill formats', 'delete bill formats'
            ],
            'documents' => [
                'view documents', 'create documents', 'upload documents', 'edit documents', 'delete documents', 'restore documents', 'force delete documents', 'download documents',
                'view activity', 'manage categories', 'manage folders', 'view document reports', 'manage document trash'
            ],
            'settings' => [
                'manage settings'
            ],
            'activity_logs' => [
                'view activity logs'
            ],
        ];

        // Create all permissions
        $allCreatedPermissions = [];
        foreach ($permissions as $group => $perms) {
            foreach ($perms as $perm) {
                $p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
                $allCreatedPermissions[] = $p;
            }
        }

        // 1. Super Admin - Grant ALL permissions without exception
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Company Admin - Grant all operational & master permissions except system logs/settings
        $companyAdmin = Role::firstOrCreate(['name' => 'Company Admin', 'guard_name' => 'web']);
        $companyAdminPermissions = Permission::whereNotIn('name', ['manage settings', 'view activity logs'])->get();
        $companyAdmin->syncPermissions($companyAdminPermissions);

        // 3. Branch Manager
        $branchManager = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);
        $branchManagerPermissions = Permission::whereIn('name', [
            'view branches', 'view users', 'create users', 'edit users',
            'view bulties', 'create bulties', 'edit bulties', 'cancel bulties', 'print bulties',
            'view trips', 'create trips', 'edit trips',
            'view vehicles', 'view drivers',
            'view reports', 'export reports',
            'view billing', 'create billing', 'view invoices', 'print invoices',
            'view toll bills',
            'view letterheads',
            'view driver salary', 'view driver salary slips', 'generate driver salary slips',
            'view employee salary', 'view attendance', 'mark attendance',
            'view leaves', 'create leaves',
            'view employee advances', 'create employee advances',
            'view company loans', 'view vehicle loans',
            'view service schedules', 'view spare parts', 'view maintenance history',
            'view breakdowns', 'create breakdowns', 'mark breakdowns resolved',
            'view tyre management',
            'view consignors', 'view consignees', 'view vehicles', 'view drivers',
            'view companies', 'view branches',
            'view items', 'view fuel pumps', 'view banks', 'view bank branches',
            'view suppliers', 'view vendors', 'view bill formats',
            'view cities', 'view packagings', 'view units',
            'view fuel companies', 'view adblue companies',
            'view tyre brands', 'view tyre models', 'view tyre sizes',
            'view documents', 'upload documents', 'download documents',
            'view customer ledger', 'view sales ledger', 'view tds report',
            'view trip reports', 'view bilty advance details',
            'view fuel report', 'view adblue report',
            'view fuel outstanding', 'create fuel outstanding', 'edit fuel outstanding', 'delete fuel outstanding',
            'view adblue outstanding', 'create adblue outstanding', 'edit adblue outstanding', 'delete adblue outstanding',
            'view vehicle report', 'view driver trip report',
        ])->get();
        $branchManager->syncPermissions($branchManagerPermissions);

        // 4. Accountant
        $accountant = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);
        $accountantPermissions = Permission::whereIn('name', [
            'view bulties', 'view trips', 'view vehicles', 'view drivers',
            'view reports', 'export reports',
            'view billing', 'create billing', 'edit billing', 'view invoices', 'print invoices', 'export invoices',
            'view toll bills', 'create toll bills', 'edit toll bills',
            'view letterheads',
            'view driver salary', 'create driver salary', 'edit driver salary', 'delete driver salary',
            'view driver advances', 'create driver advances', 'edit driver advances', 'delete driver advances',
            'generate driver salary slips', 'view driver salary slips', 'delete driver salary slips',
            'view employee salary', 'create employee salary', 'edit employee salary', 'delete employee salary',
            'view attendance', 'mark attendance',
            'view leaves', 'create leaves', 'approve leaves', 'reject leaves',
            'view employee advances', 'create employee advances', 'approve employee advances', 'reject employee advances', 'mark employee advances paid',
            'view company loans', 'create company loans', 'edit company loans', 'delete company loans', 'record company loan payments',
            'view vehicle loans', 'create vehicle loans',
            'view service schedules', 'view spare parts', 'view maintenance history', 'view breakdowns',
            'view tyre management',
            'view consignors', 'view consignees', 'view suppliers', 'view vendors', 'view banks', 'view bank branches', 'view gst',
            'view cities', 'view packagings', 'view units', 'view items',
            'view fuel pumps', 'view fuel companies', 'view adblue companies',
            'view tyre brands', 'view tyre models', 'view tyre sizes',
            'view bill formats',
            'view vehicles', 'view drivers',
            'view companies', 'view branches',
            'view documents', 'upload documents', 'download documents',
            'view customer ledger', 'view sales ledger', 'edit sales ledger', 'view tds report',
            'view trip reports', 'view bilty advance details',
            'view fuel report', 'view adblue report',
            'view fuel outstanding', 'create fuel outstanding', 'edit fuel outstanding', 'delete fuel outstanding',
            'view adblue outstanding', 'create adblue outstanding', 'edit adblue outstanding', 'delete adblue outstanding',
            'view vehicle report', 'view driver trip report',
            'view vehicle utilization', 'view mis report', 'view expense management',
            'view vehicle document report', 'view gst tax report', 'view profit loss report',
        ])->get();
        $accountant->syncPermissions($accountantPermissions);

        // 5. Dispatcher
        $dispatcher = Role::firstOrCreate(['name' => 'Dispatcher', 'guard_name' => 'web']);
        $dispatcherPermissions = Permission::whereIn('name', [
            'view bulties', 'create bulties', 'edit bulties', 'print bulties',
            'view trips', 'create trips', 'edit trips',
            'view vehicles', 'view drivers', 'view consignors', 'view consignees',
            'view letterheads',
            'view documents',
        ])->get();
        $dispatcher->syncPermissions($dispatcherPermissions);

        // 6. Driver
        $driver = Role::firstOrCreate(['name' => 'Driver', 'guard_name' => 'web']);
        $driverPermissions = Permission::whereIn('name', [
            'view trips', 'view vehicles', 'view driver salary slips',
        ])->get();
        $driver->syncPermissions($driverPermissions);

        // 7. Operator
        $operator = Role::firstOrCreate(['name' => 'Operator', 'guard_name' => 'web']);
        $operatorPermissions = Permission::whereIn('name', [
            'view bulties', 'create bulties', 'edit bulties', 'print bulties',
            'view trips', 'create trips', 'edit trips',
            'view vehicles', 'view drivers', 'view consignors', 'view consignees',
            'view letterheads',
            'view documents',
        ])->get();
        $operator->syncPermissions($operatorPermissions);

        // 8. User
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $userPermissions = Permission::whereIn('name', [
            'view bulties', 'view trips'
        ])->get();
        $userRole->syncPermissions($userPermissions);

        // Assign Super Admin role to default admin users
        $superAdminUsers = User::whereIn('email', ['superadmin@mailinator.com', 'admin@admin.com'])->get();
        foreach ($superAdminUsers as $u) {
            if (!$u->hasRole('Super Admin')) {
                $u->assignRole('Super Admin');
            }
        }

        // Create default Super Admin user if none exists
        if (User::whereHas('roles', fn($q) => $q->where('name', 'Super Admin'))->count() === 0) {
            $superAdminUser = User::firstOrCreate(
                ['email' => 'superadmin@mailinator.com'],
                [
                    'first_name' => 'Super',
                    'last_name' => 'Admin',
                    'full_name' => 'Super Admin',
                    'slug' => 'super-admin',
                    'phone' => '9876543210',
                    'password' => Hash::make('password'),
                    'role' => 'admin',
                    'country' => 'India',
                    'country_code' => 91,
                    'status' => 'active',
                ]
            );
            $superAdminUser->assignRole('Super Admin');
        }
    }
}

