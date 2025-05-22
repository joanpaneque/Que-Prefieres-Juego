<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    protected $fillable = ['preference1', 'preference2', 'human_validated'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['preference1_votes', 'preference2_votes'];

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getPreference1VotesAttribute()
    {
        $seed = now()->timestamp;
        srand($seed);
        $randomFactor = rand(654, 782);
        
        return ($this->votes()->where('vote', 'preference1')->count() + 1) * $randomFactor;
    }
    
    public function getPreference2VotesAttribute()
    {
        $seed = now()->timestamp;
        srand($seed);
        $randomFactor = rand(654, 782);
        
        return ($this->votes()->where('vote', 'preference2')->count() + 1) * $randomFactor;
    }

}
