<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    protected $fillable = ['preference_id', 'vote'];

    public function preference()
    {
        return $this->belongsTo(Preference::class);
    }
}
