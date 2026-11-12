<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('publisher')->nullable();
            $table->string('isbn')->nullable()->unique();
            $table->string('genre');
            $table->string('language')->default('Indonesia');
            $table->unsignedSmallInteger('published_year');
            $table->text('synopsis')->nullable();
            $table->string('cover', 1000)->nullable();
            $table->string('shelf')->nullable();
            $table->string('dewey_code', 30)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('ebook')->default(false);
            $table->boolean('popular')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
