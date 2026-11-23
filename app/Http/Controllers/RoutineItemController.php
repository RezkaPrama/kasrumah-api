<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoutineItemResource;
use App\Support\DefaultBudget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoutineItemController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate($this->rules($request));

        $item = $request->user()->routineItems()->create([
            'budget_category_id' => $data['category_id'],
            'name' => $data['name'],
            'amount' => $data['amount'],
            'is_active' => $data['is_active'] ?? true,
            'is_paid' => $data['is_paid'] ?? false,
        ]);

        return (new RoutineItemResource($item))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id)
    {
        $item = $request->user()->routineItems()->findOrFail($id);

        $data = $request->validate($this->rules($request, partial: true));

        if (isset($data['category_id'])) {
            $data['budget_category_id'] = $data['category_id'];
            unset($data['category_id']);
        }

        $item->update($data);

        return new RoutineItemResource($item);
    }

    public function destroy(Request $request, int $id)
    {
        $request->user()->routineItems()->findOrFail($id)->delete();

        return response()->noContent();
    }

    /** Kembalikan 8 template rutin awal. */
    public function reset(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $user->routineItems()->delete();
            DefaultBudget::seedRoutineItems($user);
        });

        return response()->json([
            'data' => RoutineItemResource::collection($user->routineItems()->orderBy('id')->get())->resolve(),
        ]);
    }

    private function rules(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'name' => [...$required, 'string', 'max:120'],
            'amount' => [...$required, 'integer', 'min:0', 'max:999999999999'],
            // kategori harus milik user yang sedang login
            'category_id' => [
                ...$required,
                'integer',
                Rule::exists('budget_categories', 'id')->where('user_id', $request->user()->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'is_paid' => ['sometimes', 'boolean'],
        ];
    }
}
