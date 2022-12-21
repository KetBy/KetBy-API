<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->integer('owner_id');
            $table->integer('public')->default(1);
            $table->integer('forked_from')->nullable();
            $table->integer('forks_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->integer('next_file_index')->default(1);
            $table->string('token')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }

    /**
     * Get owner.
     */
    public function owner() 
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get files.
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }
};
