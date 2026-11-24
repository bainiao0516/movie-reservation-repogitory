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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('thumbnail_path')->nullable();
            $table->string('rating', 10)->nullable();
            $table->string('director')->nullable();
            $table->text('cast')->nullable();
            $table->text('description')->nullable();
            $table->date('release_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
