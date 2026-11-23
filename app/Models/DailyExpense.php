<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyExpense extends Model
{
    protected $fillable = [
        'user_id',
        'budget_category_id',
        'title',
        'amount',
        'date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }
}
