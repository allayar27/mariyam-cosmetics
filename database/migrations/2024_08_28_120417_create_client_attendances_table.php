<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('client_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\v1\Clients::class);
            $table->foreignIdFor(\App\Models\v1\Device::class);
            $table->dateTime('date');
            $table->float('score');
            $table->enum('status', ['regular', 'new']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_attendances');
    }
};
