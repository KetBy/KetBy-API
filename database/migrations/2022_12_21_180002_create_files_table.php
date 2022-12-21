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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->integer('project_id');
            $table->integer('file_index');
            $table->integer('creator_id');
            $table->string('title');
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
        Schema::dropIfExists('files');
    }

    /**
     * Get the creator of the file.
     */
    public function creator()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the file's project.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
};
