<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = ['user_id', 'name', 'target_amount', 'saved_amount', 'currency', 'deadline', 'status'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
