<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use App\Models\User;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $oldAdmin = User::where("email", "admin@gmail.com")->first();
        $adminRole = Role::where("name", "Admin")->first();

        if (!isset($oldAdmin) || empty($oldAdmin)) {
            $user =  [
                [
                    'name'            => 'Admin',
                    'email'           => 'admin@gmail.com',
                    'password'        => Hash::make('admin123'),
                    'default_currency'=> 'PKR',
                ],
            ];
            foreach ($user as $value) {
                $oldAdmin =  User::create([
                    'name'             => $value['name'],
                    'email'            => $value['email'],
                    'password'         => $value['password'],
                    'default_currency' => $value['default_currency']
                ]);
            }
        }

        if (!isset($adminRole) || empty($adminRole)) {
            $adminRole = Role::create([
                'name' => 'Admin',
                'guard_name' => "web",
            ]);
        }

        if ((isset($oldAdmin) && !empty($oldAdmin)) && isset($adminRole) && !empty($adminRole)) {
            $oldAdmin->syncRoles([$adminRole->id]);
        }
    }
}
