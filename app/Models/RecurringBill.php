<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringBill extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'currency',
        'category_id',
        'due_date',
        'status'
    ];

    // CRITICAL FIX: Add this relationship
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Optional: User relationship bhi add kar dein safe side ke liye
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
