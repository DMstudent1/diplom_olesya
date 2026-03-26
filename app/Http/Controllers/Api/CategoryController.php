<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\Facades\DataTables;


class CategoryController extends Controller
{
    private function query()
    {
        return Category::query()
        ->from('categories as child')
        ->select([
            'child.id',
            'child.name',
            'child.created_at',
            'child.updated_at',
            'parent.name as parent_name',
        ])
        ->leftJoin('categories as parent', 'parent.id', '=', 'child.parent_id');
    }

private function applyFilters(Builder $query, Request $request): void
{
    if (!$request->has('search')) {
        return;
    }
    
    $search = $request->input('search');
    
    if (is_array($search)) {
        $search = $search['value'] ?? null;
    }
    
    if (empty($search)) {
        return;
    }
    
    $query->where(function (Builder $query) use ($search) {
        $query->where('child.name', 'LIKE', "%{$search}%")
            ->orWhere('child.slug', 'LIKE', "%{$search}%")
            ->orWhere('parent.name', 'LIKE', "%{$search}%");
    });
}

    public function getDataTable(Request $request)
    {
        return DataTables::eloquent($this->query())
            ->setTransformer(function (Category $category) {
                return [
                    ...$category->toArray(),
                ];
            })
            ->filter(function (Builder $query) use ($request) {
                $this->applyFilters($query, $request);
            })->toJson();
    }
    
    public function index()
    {
        return Category::all();
    }

    public function show(Category $category)
    {
        return response()->json($category);
    }

    public function store(CategoryRequest $request)
    {
        Category::create($request->validated());
    }
    
    public function update(Category $category, CategoryRequest $request)
    {
        $category->update($request->validated());
    }

    public function destroy(Category $category)
    {
        $category->delete();
    }
}