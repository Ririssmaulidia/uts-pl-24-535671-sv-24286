<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    public const CATEGORIES = ['Makan', 'Transport', 'Kuliah', 'Hiburan', 'Lainnya'];

    protected $fillable = [
        'title',
        'amount',
        'category',
        'spent_on',
        'note',
    ];

    protected $casts = [
        'amount' => 'integer',
        'spent_on' => 'date:Y-m-d',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
