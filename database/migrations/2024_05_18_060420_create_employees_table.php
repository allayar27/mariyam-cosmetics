<?php

use App\Models\v1\Employee;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('password');
            $table->enum('role',['admin','superadmin','moderator'])->default('moderator');
            $table->softDeletes();
            $table->timestamps();
        });

        Employee::create([
           'name' => 'Employee',
           'email' => 'employee@example.com',
           'role' => 'admin',
           'phone' => '987654321',
           'password' => Hash::make('12345678')
        ]);
        Employee::create([
            'name' => 'Temur',
            'email' => 'temur@example.com',
            'role' => 'superadmin',
            'phone' => '885610180',
            'password' => Hash::make('QWERTYUUJHGfds41246724688'),
        ]);
        Employee::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'superadmin',
            'phone' => '987654321',
            'password' => Hash::make('12345678')
        ]);
    }

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
