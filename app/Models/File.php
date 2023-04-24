<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'file_index',
        'creator_id',
        'title',
        'stats_cache'
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'creator_id',
        'created_at',
        'updated_at',
        'stats_cache'
    ];

    public function getMeta() {
        if ($this->meta == NULL ){
            return (object) [
                "qubits" => 1,
                "bits" => 1
            ];
        }
        $meta = json_decode($this->meta);
        if ($meta->bits < 1) {
            $meta->bits = 1;
        }
        return $meta;
    }

    public function getContent() {
        $data = json_decode($this->content);
        return $data? $data : [];
    }
}
