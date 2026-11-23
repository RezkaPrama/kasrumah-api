<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\RoutineItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    /** Snapshot lengkap untuk layar aplikasi. Opsional: ?from=YYYY-MM-DD&to=YYYY-MM-DD untuk pengeluaran harian. */
    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $user = $request->user();

        $expenses = $user->expenses()
            ->when($request->filled('from'), fn ($q) => $q->where('date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('date', '<=', $request->query('to')))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => [
                'monthlyIncome' => (int) $user->monthly_income,
                'categories' => CategoryResource::collection($user->categories()->orderBy('id')->get())->resolve(),
                'routineItems' => RoutineItemResource::collection($user->routineItems()->orderBy('id')->get())->resolve(),
                'expenses' => ExpenseResource::collection($expenses)->resolve(),
            ],
        ]);
    }

    public function updateIncome(Request $request): JsonResponse
    {
        $data = $request->validate([
            'monthly_income' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ]);

        $user = $request->user();
        $user->update(['monthly_income' => $data['monthly_income']]);

        return response()->json(['data' => ['monthlyIncome' => (int) $user->monthly_income]]);
    }
}
