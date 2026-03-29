<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('film_ratings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('tmdb_id')->nullable()->index();
            $table->unsignedTinyInteger('rating');
            $table->text('notes')->nullable();
            $table->timestamp('watched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('film_ratings');
    }
};
