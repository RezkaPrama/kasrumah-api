<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutineItem extends Model
{
    protected $fillable = [
        'user_id',
        'budget_category_id',
        'name',
        'amount',
        'is_active',
        'is_paid',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_active' => 'boolean',
            'is_paid' => 'boolean',
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
