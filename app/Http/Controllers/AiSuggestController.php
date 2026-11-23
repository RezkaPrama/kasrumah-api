<?php

namespace App\Http\Controllers;

use App\Services\GeminiAdvisor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AiSuggestController extends Controller
{
    public function __invoke(Request $request, GeminiAdvisor $advisor): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'focusGoal' => ['nullable', 'string', 'max:200'],
            'customNote' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $start = Carbon::createFromFormat('Y-m-d', $data['month'].'-01')->startOfDay();
        $end = $start->copy()->endOfMonth();

        // Semua angka dihitung ulang dari database — tidak mempercayai angka dari klien.
        $categories = $user->categories()->orderBy('id')->get();
        $routines = $user->routineItems()->with('category:id,name')->orderBy('id')->get();
        $expenses = $user->expenses()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $routineTotal = (int) $routines->where('is_active', true)->sum('amount');
        $dailyTotal = (int) $expenses->sum('amount');
        $income = (int) $user->monthly_income;

        $categoryLines = $categories->map(function ($cat) use ($routines, $expenses) {
            $spent = (int) $routines->where('is_active', true)->where('budget_category_id', $cat->id)->sum('amount')
                + (int) $expenses->where('budget_category_id', $cat->id)->sum('amount');

            return ['name' => $cat->name, 'limit' => (int) $cat->limit, 'spent' => $spent];
        })->values()->all();

        $routineLines = $routines->map(fn ($r) => [
            'name' => $r->name,
            'amount' => (int) $r->amount,
            'categoryName' => $r->category?->name ?? 'Lainnya',
            'isActive' => (bool) $r->is_active,
            'isPaid' => (bool) $r->is_paid,
        ])->values()->all();

        try {
            $result = $advisor->advise([
                'monthLabel' => $start->locale('id')->translatedFormat('F Y'),
                'monthlyIncome' => $income,
                'routineTotal' => $routineTotal,
                'dailyTotal' => $dailyTotal,
                'remainingBalance' => $income - $routineTotal - $dailyTotal,
                'focusGoal' => $data['focusGoal'] ?? '',
                'customNote' => $data['customNote'] ?? '',
                'routineItems' => $routineLines,
                'categories' => $categoryLines,
            ]);
        } catch (RuntimeException $e) {
            $status = in_array($e->getCode(), [429, 500, 502, 503], true) ? $e->getCode() : 502;

            return response()->json(['message' => $e->getMessage()], $status);
        }

        return response()->json(['data' => $result]);
    }
}
