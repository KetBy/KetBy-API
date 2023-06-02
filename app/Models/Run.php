<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Westworld\TimeAgo;

class Run extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'results',
        'run_index'
    ];

    public function getCreatedAt() 
    {
        $timeAgo = new \Westsworld\TimeAgo();
        return $timeAgo->inWordsFromStrings($this->created_at);
    }

    public function getResults() {
        if ($this->results == null){
            return null;
        }
        $results = is_string($this->results)? json_decode($this->results) : $this->results;
        return $results;
    }

    public function getShots() {
        $results = $this->getResults();
        if (!$results) {
            return 0;
        }
        $sum = 0;
        foreach ($results as $result)  {
            $sum += $result;
        }
        return $sum;
    }
}
