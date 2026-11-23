<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExpenseResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate($this->rules($request));

        $expense = $request->user()->expenses()->create($this->attributes($data));

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id)
    {
        $expense = $request->user()->expenses()->findOrFail($id);

        $data = $request->validate($this->rules($request));

        $expense->update($this->attributes($data));

        return new ExpenseResource($expense);
    }

    public function destroy(Request $request, int $id)
    {
        $request->user()->expenses()->findOrFail($id)->delete();

        return response()->noContent();
    }

    private function rules(Request $request): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('budget_categories', 'id')->where('user_id', $request->user()->id),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function attributes(array $data): array
    {
        return [
            'budget_category_id' => $data['category_id'],
            'title' => $data['title'],
            'amount' => $data['amount'],
            'date' => $data['date'],
            'note' => $data['note'] ?? null,
        ];
    }
}
