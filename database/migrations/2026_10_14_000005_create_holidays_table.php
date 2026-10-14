<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('date')->nullable();
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['date', 'weekday', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
