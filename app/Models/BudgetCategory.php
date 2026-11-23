<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetCategory extends Model
{
    protected $fillable = ['user_id', 'name', 'limit', 'color'];

    protected function casts(): array
    {
        return ['limit' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function routineItems(): HasMany
    {
        return $this->hasMany(RoutineItem::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(DailyExpense::class);
    }
}
