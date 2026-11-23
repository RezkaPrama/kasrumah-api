<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\RoutineItem */
class RoutineItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => (int) $this->amount,
            'categoryId' => $this->budget_category_id,
            'isActive' => (bool) $this->is_active,
            'isPaid' => (bool) $this->is_paid,
        ];
    }
}
