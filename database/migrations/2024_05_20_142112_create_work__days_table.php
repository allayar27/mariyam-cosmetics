<?php

use App\Models\v1\Branch;
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
        Schema::create('work__days', function (Blueprint $table) {
            $table->id();
            $table->date('work_day');
            $table->foreignIdFor(Branch::class);
            $table->integer('total_workers')->nullable();
            $table->integer('workers_count')->nullable();
            $table->integer('late_workers')->nullable();
            $table->enum('type',['work_day', 'none'])->default('none');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work__days');
    }
};
