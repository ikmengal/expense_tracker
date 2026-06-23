<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'guard_name' => 'web'
            ],
            [
                'name' => 'User',
                'guard_name' => 'web'
            ]
        ];

        foreach ($roles as $key => $role) {
            $exist = Role::where('name', $role['name'])->first();
            if(empty($exist)){
                Role::create([
                    'name' => $role['name'],
                    'guard_name' => $role['guard_name']
                ]);
            }
        }
    }
}
