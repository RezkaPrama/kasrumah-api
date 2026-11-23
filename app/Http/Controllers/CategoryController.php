<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'limit' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $category = $request->user()->categories()->create([
            'name' => $data['name'],
            'limit' => $data['limit'],
            'color' => $data['color'] ?? '#475569',
        ]);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id)
    {
        $category = $request->user()->categories()->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:80'],
            'limit' => ['sometimes', 'required', 'integer', 'min:0', 'max:999999999999'],
            'color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $category->update($data);

        return new CategoryResource($category);
    }
}
