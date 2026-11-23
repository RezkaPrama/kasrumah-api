<?php

use App\Http\Controllers\AiSuggestController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\RoutineItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('budget', [BudgetController::class, 'show']);
    Route::put('income', [BudgetController::class, 'updateIncome']);

    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{id}', [CategoryController::class, 'update'])->whereNumber('id');

    // 'reset' harus didaftarkan sebelum rute {id}
    Route::post('routine-items/reset', [RoutineItemController::class, 'reset']);
    Route::post('routine-items', [RoutineItemController::class, 'store']);
    Route::put('routine-items/{id}', [RoutineItemController::class, 'update'])->whereNumber('id');
    Route::delete('routine-items/{id}', [RoutineItemController::class, 'destroy'])->whereNumber('id');

    Route::post('expenses', [ExpenseController::class, 'store']);
    Route::put('expenses/{id}', [ExpenseController::class, 'update'])->whereNumber('id');
    Route::delete('expenses/{id}', [ExpenseController::class, 'destroy'])->whereNumber('id');

    Route::post('ai-suggest', AiSuggestController::class)->middleware('throttle:10,1');
});
