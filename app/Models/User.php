<?php
namespace App\Models;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'avatar_url',
        'cover_url'
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'email',
        'fb_id',
        'created_at',
        'updated_at',
        'remember_token',
        'id'
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }    

    /**
     * Get user projects.
     * 
     */
    public function projects() 
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function getAvatarUrlAttribute() 
    {
        return env("CDN_URL") . $this->attributes['avatar_url'];
    }

    public function getCoverUrlAttribute() 
    {
        return env("CDN_URL") . $this->attributes['cover_url'];
    }

    public function getCreatedAt() {
        return date_format($this->created_at, "j M Y");
    }
}