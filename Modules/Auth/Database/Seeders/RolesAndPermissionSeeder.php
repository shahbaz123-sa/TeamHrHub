<?php

namespace Modules\Auth\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Auth\Models\PermissionModule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Super Admin',
            'Admin',
            'Manager',
            'Employee',
            'Hr',
            'Finance'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $modules = [
            [
                'name' => 'Hrm',
                'permissions' => $this->getHrmPermissions()
            ],
            [
                'name' => 'Crm',
                'permissions' => $this->getCrmPermissions()
            ],
            [
                'name' => 'Chat',
                'permissions' => $this->getChatPermissions()
            ]
        ];

        $actions = ['create', 'read', 'update', 'delete'];

        foreach ($modules as $module) {
            $moduleId = PermissionModule::updateOrCreate(['name' => $module['name']])->id;
            foreach ($module['permissions'] as $permission) {
                foreach ($actions as $action) {
                    $permissionName = strtolower(str_replace(' ', '_', $permission)) . '.' . $action;
                    Permission::updateOrCreate(['name' => $permissionName], [
                        'name' => $permissionName,
                        'module_id' => $moduleId,
                    ]);
                }
            }
        }

        Role::findByName('Super Admin')->syncPermissions(Permission::all());

        User::whereIn('email', [
            'admin@zarea.com',
            'dev.admin@zarea.com'
        ])->each(fn($user) => $user->syncRoles(['Super Admin']));

        // ✅ Clear and rebuild Spatie permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        \Spatie\Permission\Models\Permission::get(); // Rebuild cache
    }

    public function getCrmPermissions(): array
    {
        return [
            'Product Tag',
            'Product Category',
            'Product Attribute',
            'Product Attribute Value',
            'Product',
            'Product UOM',
            'Product City',
            'Product Brand',
            'Customer',
            'Order',
            'Latest Report',
            'Financial Report',
            'Notice Type',
            'Notice',
            'Product Graph Price',
            'Post',
            'Post Tag',
            'Post Category',
            'Email Setting',
            'Rfq',
            'Credit Application',
            'Rfq Manager',
            'Supplier',
            'Product Daily Price',
        ];
    }

    public function getHrmPermissions(): array
    {
        return [
            'Employee Dashboard',
            'CEO Dashboard',
            'Reports',
            'Employee',
            'Employee Rules',
            'Leave',
            'Leave Hr Approval',
            'Leave Manager Approval',
            'Attendance',
            'Asset',
            'Ticket',
            'Company Policy',
            'Role',
            'Branch',
            'Department',
            'Designation',
            'Leave Type',
            'Document Type',
            'Payslip Type',
            'Allowance Option',
            'Loan Option',
            'Deduction Option',
            'Goal Type',
            'Training Type',
            'Award Type',
            'Employment Status',
            'Employment Type',
            'Termination Type',
            'Job Category',
            'Job Stage',
            'Performance Type',
            'Competency',
            'Expense Type',
            'Ticket Category',
            'Asset Type',
            'Payroll Generation',
            'Hr Dashboard',
            'Holidays',
            'Asset Attributes',
            'weekly attendance report',
            'monthly attendance report',
            'attendance summary report',
            'employee attendance status report',
            'logged in users',
            'Asset Assignment History',
            'Employee Salary',
            'Approve Payroll',
            'Tax Slab',
        ];
    }

    public function getChatPermissions(): array
    {
        return [
            'Chat'
        ];
    }
}
