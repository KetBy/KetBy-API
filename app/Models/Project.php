<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    /**
     * Get the files for the project.
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }

    /**
     * Get the owener of the project.
     */
    public function owner()
    {
        return $this->belongsTo(User::class);
    }
}
