<?php

use App\Models\v1\Schedule;
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
        Schema::create('weeklies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Schedule::class);
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('day',['monday','tuesday','wednesday','thursday','friday','saturday','sunday']);
            $table->boolean('is_work_day')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weeklies');
    }
};
