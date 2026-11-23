<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DailyExpense */
class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'amount' => (int) $this->amount,
            'categoryId' => $this->budget_category_id,
            'date' => $this->date->format('Y-m-d'),
            'note' => $this->note,
        ];
    }
}
