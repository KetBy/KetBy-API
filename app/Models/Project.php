<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'thumbnail_url',
        'owner_id',
        'public',
        'forked_from',
        'forks_count',
        'stars_count',
        'views_count',
        'next_file_index',
        'token',
        'highlighted'
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'views_count',
        'owner_id',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the files of the project.
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
        return $this->belongsTo(User::class, "owner_id");
    }

    public function getCreatedAt() 
    {
        return date_format($this->created_at, "j M Y");
    }

    public function getForksCount() {
        return Project::where('forked_from', '=', $this->id)->get()->count();
    }

    public static function boot() {
        parent::boot();
        self::deleting(function($project) { 
             $project->files()->each(function($file) {
                $file->delete();
             });
        });
    }
}
