<?php

use App\Models\v1\Branch;
use App\Models\v1\User;
use App\Models\v1\Device;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Branch::class);
            $table->time('time');
            $table->foreignIdFor(Device::class);
            $table->enum('type',['in','out','none',]);
            $table->float('score');
            $table->dateTime('day');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
