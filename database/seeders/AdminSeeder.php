<?php

namespace Database\Seeders;

use App\Models\v1\Employee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::create([
            'name' => 'Admin2',
            'email' => 'adminapteka@mail.com',
            'role' => 'superadmin',
            'phone' => '12345678',
            'password' => Hash::make('!AdminApteka@1234')
        ]);
    }
}
